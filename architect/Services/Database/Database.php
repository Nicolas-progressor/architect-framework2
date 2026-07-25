<?php

declare(strict_types=1);

namespace Architect\Services\Database;

use Architect\Services\Database\Contracts\DatabaseInterface;
use Architect\Services\Database\Contracts\QueryLoggerInterface;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;

/**
 * Database connection service.
 * Wraps PDO with convenient methods and connection management.
 */
class Database implements DatabaseInterface
{
    private PDO $pdo;
    private string $driver;
    private string $name;
    private ?QueryLoggerInterface $queryLogger;
    private DsnBuilder $dsnBuilder;

    /**
     * Create a new database connection.
     *
     * @param array $config Connection configuration (driver, host, database, etc.)
     * @param string $name Connection name
     * @param QueryLoggerInterface|null $queryLogger Optional query logger
     * @param DsnBuilder|null $dsnBuilder Optional DSN builder (auto‑created if null)
     */
    public function __construct(
        array $config,
        string $name = 'default',
        ?QueryLoggerInterface $queryLogger = null,
        ?DsnBuilder $dsnBuilder = null
    ) {
        $this->name = $name;
        $this->queryLogger = $queryLogger;
        $this->dsnBuilder = $dsnBuilder ?? new DsnBuilder();
        $this->pdo = $this->createPdo($config);
        $this->driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /**
     * Create PDO instance from config.
     */
    private function createPdo(array $config): PDO
    {
        $driver = $config['driver'] ?? 'mysql';
        $dsn = $this->dsnBuilder->build($driver, $config);

        $options = $config['options'] ?? [];
        $defaultOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $options = array_replace($defaultOptions, $options);

        try {
            return new PDO($dsn, $config['username'] ?? '', $config['password'] ?? '', $options);
        } catch (PDOException $e) {
            throw new InvalidArgumentException(
                sprintf('Connection failed for driver "%s": %s', $driver, $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $sql, array $bindings = []): PDOStatement
    {
        $startTime = microtime(true);

        if (empty($bindings)) {
            $stmt = $this->pdo->query($sql);
        } else {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($bindings);
        }

        $duration = microtime(true) - $startTime;

        // Log query if logger is set
        $this->logQuery($sql, $duration, $bindings);

        return $stmt;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(string $sql, array $bindings = []): int
    {
        $stmt = $this->query($sql, $bindings);
        return $stmt->rowCount();
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(string $sql, array $bindings = []): ?array
    {
        $stmt = $this->query($sql, $bindings);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll(string $sql, array $bindings = []): array
    {
        $stmt = $this->query($sql, $bindings);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritdoc}
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * {@inheritdoc}
     */
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback();
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId(?string $sequence = null): string|false
    {
        return $this->pdo->lastInsertId($sequence);
    }

    /**
     * Get driver name.
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Set query logger.
     */
    public function setQueryLogger(?QueryLoggerInterface $queryLogger): void
    {
        $this->queryLogger = $queryLogger;
    }

    /**
     * Log query for debugging.
     */
    private function logQuery(string $sql, float $duration, array $bindings = []): void
    {
        if ($this->queryLogger !== null) {
            $this->queryLogger->logQuery($sql, $duration, $bindings);
        }
    }
}
