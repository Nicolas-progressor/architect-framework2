<?php

declare(strict_types=1);

namespace Axiom\Migration;

use Axiom\Orm\Connection\ConnectionManager;

/**
 * Migration manager - handles running migrations
 */
class MigrationManager
{
    private string $path;
    private string $table;
    private \PDO $pdo;
    private string $driver;

    public function __construct(string $path, string $table = 'migrations')
    {
        $this->path = $path;
        $this->table = $table;
        
        $connection = ConnectionManager::getDefault();
        $this->pdo = $connection->getPdo();
        $this->driver = $connection->getDriver();
    }

    /**
     * Get migration path
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set migration path
     */
    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Get migrations table name
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Ensure migrations table exists
     */
    public function ensureTable(): void
    {
        $table = $this->table;
        
        $sql = match ($this->driver) {
            'sqlite' => "CREATE TABLE IF NOT EXISTS {$table} (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL,
                batch INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            default => "CREATE TABLE IF NOT EXISTS {$table} (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB"
        };

        $this->pdo->query($sql);
    }

    /**
     * Get all migration files
     */
    public function getMigrationFiles(): array
    {
        if (!is_dir($this->path)) {
            return [];
        }

        $files = glob($this->path . '/*.php');
        $migrations = [];

        foreach ($files as $file) {
            $filename = basename($file, '.php');
            if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_(.+)$/', $filename, $matches)) {
                // Convert snake_case to StudlyCase for class name
                $className = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $matches[1])));
                $migrations[$filename] = [
                    'filename' => $filename,
                    'class' => $className,
                    'path' => $file
                ];
            }
        }

        ksort($migrations);
        return $migrations;
    }

    /**
     * Get ran migrations from database
     */
    public function getRanMigrations(): array
    {
        $this->ensureTable();
        
        $stmt = $this->pdo->query("SELECT migration FROM {$this->table} ORDER BY batch, id");
        return array_column($stmt->fetchAll(), 'migration');
    }

    /**
     * Get pending migrations
     */
    public function getPendingMigrations(): array
    {
        $files = $this->getMigrationFiles();
        $ran = $this->getRanMigrations();

        return array_filter($files, fn($file) => !in_array($file['filename'], $ran));
    }

    /**
     * Get next batch number
     */
    private function getNextBatch(): int
    {
        $stmt = $this->pdo->query("SELECT MAX(batch) as batch FROM {$this->table}");
        $result = $stmt->fetch();
        return (int) ($result['batch'] ?? 0) + 1;
    }

    /**
     * Run all pending migrations
     */
    public function migrate(bool $pretend = false): array
    {
        $pending = $this->getPendingMigrations();
        $ran = [];

        if (empty($pending)) {
            return $ran;
        }

        $batch = $this->getNextBatch();

        foreach ($pending as $migration) {
            if ($pretend) {
                echo "Would migrate: {$migration['filename']}\n";
                $ran[] = $migration['filename'];
                continue;
            }

            require_once $migration['path'];

            $className = $migration['class'];
            /** @var Migration $instance */
            $instance = new $className();

            try {
                $instance->up();
                
                // Record migration
                $stmt = $this->pdo->prepare("INSERT INTO {$this->table} (migration, batch) VALUES (?, ?)");
                $stmt->execute([$migration['filename'], $batch]);
                
                $ran[] = $migration['filename'];
                echo "Migrated: {$migration['filename']}\n";
            } catch (\Throwable $e) {
                echo "Failed to migrate: {$migration['filename']}\n";
                echo "Error: {$e->getMessage()}\n";
                throw $e;
            }
        }

        return $ran;
    }

    /**
     * Rollback last migration
     */
    public function rollback(bool $pretend = false): array
    {
        $this->ensureTable();

        // Get last batch
        $stmt = $this->pdo->query("SELECT MAX(batch) as batch FROM {$this->table}");
        $result = $stmt->fetch();
        $batch = $result['batch'] ?? null;

        if ($batch === null) {
            return [];
        }

        // Get migrations from last batch
        $stmt = $this->pdo->prepare("SELECT migration FROM {$this->table} WHERE batch = ? ORDER BY id DESC");
        $stmt->execute([$batch]);
        $migrations = array_column($stmt->fetchAll(), 'migration');
        $rolledBack = [];

        $files = $this->getMigrationFiles();

        foreach ($migrations as $migration) {
            if (!isset($files[$migration])) {
                continue;
            }

            $file = $files[$migration];

            if ($pretend) {
                echo "Would rollback: {$migration}\n";
                $rolledBack[] = $migration;
                continue;
            }

            require_once $file['path'];

            $className = $file['class'];
            /** @var Migration $instance */
            $instance = new $className();

            try {
                $instance->down();
                
                // Remove migration record
                $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE migration = ?");
                $stmt->execute([$migration]);
                
                $rolledBack[] = $migration;
                echo "Rolled back: {$migration}\n";
            } catch (\Throwable $e) {
                echo "Failed to rollback: {$migration}\n";
                echo "Error: {$e->getMessage()}\n";
                throw $e;
            }
        }

        return $rolledBack;
    }

    /**
     * Rollback all migrations
     */
    public function reset(bool $pretend = false): array
    {
        $this->ensureTable();

        $stmt = $this->pdo->query("SELECT migration FROM {$this->table} ORDER BY batch DESC, id DESC");
        $migrations = array_column($stmt->fetchAll(), 'migration');
        
        $files = $this->getMigrationFiles();
        $rolledBack = [];

        foreach ($migrations as $migration) {
            if (!isset($files[$migration])) {
                continue;
            }

            $file = $files[$migration];

            if ($pretend) {
                echo "Would reset: {$migration}\n";
                $rolledBack[] = $migration;
                continue;
            }

            require_once $file['path'];

            $className = $file['class'];
            /** @var Migration $instance */
            $instance = new $className();

            try {
                $instance->down();
                $rolledBack[] = $migration;
            } catch (\Throwable $e) {
                echo "Failed to reset: {$migration}\n";
                throw $e;
            }
        }

        // Clear migrations table
        if (!$pretend && !empty($rolledBack)) {
            $this->pdo->query("TRUNCATE TABLE {$this->table}");
        }

        return $rolledBack;
    }

    /**
     * Show migration status
     */
    public function status(): array
    {
        $this->ensureTable();

        $files = $this->getMigrationFiles();
        $ran = $this->getRanMigrations();

        $status = [];
        
        foreach ($files as $filename => $file) {
            $status[] = [
                'filename' => $filename,
                'ran' => in_array($filename, $ran)
            ];
        }

        return $status;
    }

    /**
     * Create migration file
     */
    public static function create(string $name, string $path): string
    {
        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_{$name}.php";
        $filepath = $path . '/' . $filename;

        $className = str_replace('-', '', ucwords($name, '-'));
        
        $template = <<<PHP
<?php

declare(strict_types=1);

use Axiom\Migration\Migration;

class {$className} extends Migration
{
    public function up(): void
    {
        \$this->create('table_name', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name');
            // Add more columns...
        });
    }

    public function down(): void
    {
        \$this->drop('table_name');
    }
}
PHP;

        file_put_contents($filepath, $template);
        
        return $filename;
    }
}
