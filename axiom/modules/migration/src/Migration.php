<?php

declare(strict_types=1);

namespace Axiom\Migration;

use Axiom\Orm\Connection\ConnectionManager;

/**
 * Migration base class
 */
abstract class Migration
{
    protected Connection $connection;

    public function __construct()
    {
        $this->connection = new Connection(ConnectionManager::getDefault());
    }

    /**
     * Run the migration
     */
    abstract public function up(): void;

    /**
     * Reverse the migration
     */
    abstract public function down(): void;

    /**
     * Create a new table
     */
    public function create(string $table, callable $callback): void
    {
        $driver = $this->connection->getDriver();
        $blueprint = new Blueprint($table, $driver);
        $callback($blueprint);

        $sql = $driver === 'sqlite'
            ? $blueprint->toSqlite()
            : $blueprint->toSql();

        $this->connection->query($sql);
    }

    /**
     * Drop a table
     */
    public function drop(string $table): void
    {
        $driver = $this->connection->getDriver();
        
        if ($driver === 'sqlite') {
            $this->connection->query("DROP TABLE IF EXISTS {$table}");
        } else {
            $this->connection->query("DROP TABLE IF EXISTS {$table}");
        }
    }

    /**
     * Drop table if exists
     */
    public function dropIfExists(string $table): void
    {
        $this->connection->query("DROP TABLE IF EXISTS {$table}");
    }

    /**
     * Rename a table
     */
    public function rename(string $from, string $to): void
    {
        $driver = $this->connection->getDriver();
        
        if ($driver === 'sqlite') {
            $this->connection->query("ALTER TABLE {$from} RENAME TO {$to}");
        } else {
            $this->connection->query("ALTER TABLE {$from} RENAME TO {$to}");
        }
    }

    /**
     * Modify existing table
     */
    public function table(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        foreach ($blueprint->getModifiers() as $modifier) {
            $sql = $this->buildModifierSql($modifier, $table);
            if ($sql) {
                $this->connection->query($sql);
            }
        }
    }

    /**
     * Build SQL for table modifier
     */
    private function buildModifierSql(array $modifier, string $table): string
    {
        return match ($modifier['type']) {
            'add_column' => "ALTER TABLE {$table} ADD COLUMN {$modifier['column']}",
            'drop_column' => "ALTER TABLE {$table} DROP COLUMN {$modifier['column']}",
            'rename_column' => "ALTER TABLE {$table} RENAME COLUMN {$modifier['from']} TO {$modifier['to']}",
            'modify_column' => "ALTER TABLE {$table} MODIFY {$modifier['column']}",
            default => '',
        };
    }

    /**
     * Check if a table exists
     */
    public function exists(string $table): bool
    {
        $driver = $this->connection->getDriver();
        if ($driver === 'mysql') {
            $result = $this->connection->query("SHOW TABLES LIKE '{$table}'");
            return $result->rowCount() > 0;
        }
        if ($driver === 'sqlite') {
            $result = $this->connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$table}'");
            return $result->rowCount() > 0;
        }
        // For other drivers, assume exists (should be implemented)
        return false;
    }

    /**
     * Get connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }
}

class Connection
{
    private \PDO $pdo;
    private string $driver;

    public function __construct(\Axiom\Orm\Connection\ConnectionInterface $connection)
    {
        $this->pdo = $connection->getPdo();
        $this->driver = $connection->getDriver();
    }

    public function query(string $sql): \PDOStatement
    {
        // Split by semicolon and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $lastResult = null;
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $lastResult = $this->pdo->query($statement);
            }
        }
        
        return $lastResult;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }
}
