<?php

declare(strict_types=1);

namespace Axiom\Migration;

/**
 * Blueprint for table schema definition
 */
class Blueprint
{
    private string $table;
    private array $columns = [];
    private array $modifiers = [];
    private array $indexes = [];
    private ?string $primaryKey = null;
    private string $driver = 'mysql';

    public function __construct(string $table, string $driver = 'mysql')
    {
        $this->table = $table;
        $this->driver = $driver;
    }

    /**
     * Get table name
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get driver
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Get columns
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Get modifiers (for ALTER TABLE)
     */
    public function getModifiers(): array
    {
        return $this->modifiers;
    }

    /**
     * Get indexes
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * Big integer auto-incrementing ID
     */
    public function id(string $column = 'id'): self
    {
        return $this->bigIncrements($column);
    }

    /**
     * Big integer auto-incrementing
     */
    public function bigIncrements(string $column): self
    {
        $this->columns[$column] = [
            'type' => 'BIGINT',
            'auto_increment' => true,
            'unsigned' => true
        ];
        $this->primaryKey = $column;
        return $this;
    }

    /**
     * Integer auto-incrementing
     */
    public function increments(string $column): self
    {
        $this->columns[$column] = [
            'type' => 'INT',
            'auto_increment' => true,
            'unsigned' => true
        ];
        $this->primaryKey = $column;
        return $this;
    }

    /**
     * Big integer
     */
    public function bigInteger(string $column, bool $autoIncrement = false): self
    {
        $this->columns[$column] = [
            'type' => 'BIGINT',
            'auto_increment' => $autoIncrement,
            'unsigned' => true
        ];
        return $this;
    }

    /**
     * Integer
     */
    public function integer(string $column): self
    {
        $this->columns[$column] = ['type' => 'INT'];
        return $this;
    }

    /**
     * Small integer
     */
    public function smallInteger(string $column): self
    {
        $this->columns[$column] = ['type' => 'SMALLINT'];
        return $this;
    }

    /**
     * Tiny integer
     */
    public function tinyInteger(string $column): self
    {
        $this->columns[$column] = ['type' => 'TINYINT'];
        return $this;
    }

    /**
     * String (VARCHAR)
     */
    public function string(string $column, int $length = 255): self
    {
        $this->columns[$column] = [
            'type' => 'VARCHAR',
            'length' => $length
        ];
        return $this;
    }

    /**
     * Text
     */
    public function text(string $column): self
    {
        $this->columns[$column] = ['type' => 'TEXT'];
        return $this;
    }

    /**
     * Long text
     */
    public function longText(string $column): self
    {
        $this->columns[$column] = ['type' => 'LONGTEXT'];
        return $this;
    }

    /**
     * Medium text
     */
    public function mediumText(string $column): self
    {
        $this->columns[$column] = ['type' => 'MEDIUMTEXT'];
        return $this;
    }

    /**
     * JSON
     */
    public function json(string $column): self
    {
        $this->columns[$column] = ['type' => 'JSON'];
        return $this;
    }

    /**
     * Boolean
     */
    public function boolean(string $column): self
    {
        $this->columns[$column] = ['type' => 'BOOLEAN'];
        return $this;
    }

    /**
     * Date
     */
    public function date(string $column): self
    {
        $this->columns[$column] = ['type' => 'DATE'];
        return $this;
    }

    /**
     * DateTime
     */
    public function dateTime(string $column): self
    {
        $this->columns[$column] = ['type' => 'DATETIME'];
        return $this;
    }

    /**
     * Timestamp
     */
    public function timestamp(string $column): self
    {
        $this->columns[$column] = ['type' => 'TIMESTAMP'];
        return $this;
    }

    /**
     * Timestamps (created_at, updated_at)
     */
    public function timestamps(): self
    {
        $this->columns['created_at'] = ['type' => 'TIMESTAMP'];
        $this->columns['updated_at'] = ['type' => 'TIMESTAMP'];
        return $this;
    }

    /**
     * Time
     */
    public function time(string $column): self
    {
        $this->columns[$column] = ['type' => 'TIME'];
        return $this;
    }

    /**
     * Decimal
     */
    public function decimal(string $column, int $precision = 10, int $scale = 2): self
    {
        $this->columns[$column] = [
            'type' => 'DECIMAL',
            'precision' => $precision,
            'scale' => $scale
        ];
        return $this;
    }

    /**
     * Float
     */
    public function float(string $column): self
    {
        $this->columns[$column] = ['type' => 'FLOAT'];
        return $this;
    }

    /**
     * Double
     */
    public function double(string $column): self
    {
        $this->columns[$column] = ['type' => 'DOUBLE'];
        return $this;
    }

    /**
     * Enum
     */
    public function enum(string $column, array $values): self
    {
        $this->columns[$column] = [
            'type' => 'ENUM',
            'values' => $values
        ];
        return $this;
    }

    /**
     * UUID
     */
    public function uuid(string $column): self
    {
        $this->columns[$column] = ['type' => 'CHAR', 'length' => 36];
        return $this;
    }

    /**
     * Set nullable
     */
    public function nullable(): self
    {
        end($this->columns);
        $key = key($this->columns);
        $this->columns[$key]['nullable'] = true;
        return $this;
    }

    /**
     * Set default value
     */
    public function default(mixed $value): self
    {
        end($this->columns);
        $key = key($this->columns);
        $this->columns[$key]['default'] = $value;
        return $this;
    }

    /**
     * Set as unsigned
     */
    public function unsigned(): self
    {
        end($this->columns);
        $key = key($this->columns);
        $this->columns[$key]['unsigned'] = true;
        return $this;
    }

    /**
     * Set comment
     */
    public function comment(string $comment): self
    {
        end($this->columns);
        $key = key($this->columns);
        $this->columns[$key]['comment'] = $comment;
        return $this;
    }

    /**
     * Set unique
     */
    public function unique(): self
    {
        end($this->columns);
        $key = key($this->columns);
        $this->indexes[] = [
            'type' => 'unique',
            'columns' => [$key],
            'name' => $key . '_unique'
        ];
        return $this;
    }

    /**
     * Add foreign key
     */
    public function foreign(string $column): ForeignKey
    {
        return new ForeignKey($this, $column);
    }

    /**
     * Add index
     */
    public function index(array|string $columns, ?string $name = null): self
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $this->indexes[] = [
            'type' => 'index',
            'columns' => $columns,
            'name' => $name ?? implode('_', $columns) . '_index'
        ];
        return $this;
    }

    /**
     * Add unique index
     */
    public function uniqueIndex(array|string $columns, ?string $name = null): self
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $this->indexes[] = [
            'type' => 'unique',
            'columns' => $columns,
            'name' => $name ?? implode('_', $columns) . '_unique'
        ];
        return $this;
    }

    /**
     * Add primary key
     */
    public function primary(array|string $columns): self
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $this->indexes[] = [
            'type' => 'primary',
            'columns' => $columns
        ];
        return $this;
    }

    // ========== MODIFIERS FOR ALTER TABLE ==========

    /**
     * Add column (for existing table)
     */
    public function addColumn(string $type, string $column, array $options = []): self
    {
        $this->modifiers[] = [
            'type' => 'add_column',
            'column' => $this->buildColumnDefinition($type, $column, $options)
        ];
        return $this;
    }

    /**
     * Drop column
     */
    public function dropColumn(string $column): self
    {
        $this->modifiers[] = [
            'type' => 'drop_column',
            'column' => $column
        ];
        return $this;
    }

    /**
     * Rename column
     */
    public function renameColumn(string $from, string $to): self
    {
        $this->modifiers[] = [
            'type' => 'rename_column',
            'from' => $from,
            'to' => $to
        ];
        return $this;
    }

    /**
     * Modify column
     */
    public function modifyColumn(string $type, string $column, array $options = []): self
    {
        $this->modifiers[] = [
            'type' => 'modify_column',
            'column' => $this->buildColumnDefinition($type, $column, $options)
        ];
        return $this;
    }

    // ========== SQL GENERATION ==========

    /**
     * Generate CREATE TABLE SQL
     */
    public function toSql(): string
    {
        $definitions = [];

        foreach ($this->columns as $column => $options) {
            $definitions[] = $this->buildColumnSql($column, $options);
        }

        foreach ($this->indexes as $index) {
            if ($index['type'] === 'primary') {
                $definitions[] = "PRIMARY KEY (" . implode(', ', $index['columns']) . ")";
            } elseif ($index['type'] === 'unique') {
                $definitions[] = "UNIQUE KEY {$index['name']} (" . implode(', ', $index['columns']) . ")";
            }
        }

        $sql = "CREATE TABLE {$this->table} (\n";
        $sql .= implode(",\n", array_map(fn($def) => "    " . $def, $definitions));
        $sql .= "\n) ENGINE=InnoDB";

        // Add regular indexes (not unique) - these need separate statements
        foreach ($this->indexes as $index) {
            if ($index['type'] === 'index') {
                $sql .= ";\nCREATE INDEX {$index['name']} ON {$this->table} (" . implode(', ', $index['columns']) . ")";
            }
        }

        return $sql;
    }

    /**
     * Generate SQLite CREATE TABLE SQL
     */
    public function toSqlite(): string
    {
        $definitions = [];

        foreach ($this->columns as $column => $options) {
            $definitions[] = $this->buildColumnSql($column, $options);
        }

        foreach ($this->indexes as $index) {
            if ($index['type'] === 'primary') {
                $definitions[] = "PRIMARY KEY (" . implode(', ', $index['columns']) . ")";
            }
        }

        return "CREATE TABLE {$this->table} (\n" .
            implode(",\n", array_map(fn($def) => "    " . $def, $definitions)) .
            "\n)";
    }

    /**
     * Build column SQL definition
     */
    private function buildColumnSql(string $column, array $options): string
    {
        $sql = "{$column} " . $this->getColumnType($options);

        if (!empty($options['auto_increment'])) {
            $sql .= " AUTO_INCREMENT";
        }

        if (empty($options['nullable'])) {
            $sql .= " NOT NULL";
        }

        if (array_key_exists('default', $options)) {
            $default = $options['default'];
            
            // Handle boolean defaults
            if (is_bool($default)) {
                $default = $default ? 1 : 0;
            }
            
            if (is_string($default)) {
                $sql .= " DEFAULT '{$default}'";
            } elseif (is_numeric($default)) {
                $sql .= " DEFAULT {$default}";
            } elseif (is_null($default)) {
                // Don't add DEFAULT for null
            }
        }

        if (!empty($options['unique']) && empty($options['auto_increment'])) {
            $sql .= " UNIQUE";
        }

        // Add PRIMARY KEY for auto_increment column if not already added
        if (!empty($options['auto_increment'])) {
            $hasPrimaryKey = false;
            foreach ($this->indexes as $index) {
                if ($index['type'] === 'primary' && in_array($column, $index['columns'])) {
                    $hasPrimaryKey = true;
                    break;
                }
            }
            if (!$hasPrimaryKey) {
                $sql .= " PRIMARY KEY";
            }
        }

        return $sql;
    }

    /**
     * Build column definition for ALTER
     */
    private function buildColumnDefinition(string $type, string $column, array $options): string
    {
        $options['type'] = $type;
        return $column . ' ' . $this->getColumnType($options);
    }

    /**
     * Get SQL type for column
     */
    private function getColumnType(array $options): string
    {
        $type = $options['type'] ?? 'VARCHAR';
        $length = $options['length'] ?? null;
        $precision = $options['precision'] ?? 10;
        $scale = $options['scale'] ?? 2;

        // Handle special types
        if ($type === 'BOOLEAN') {
            return 'TINYINT(1)';
        }
        
        if ($type === 'JSON' && $this->driver === 'mysql') {
            return 'JSON';
        }

        // SQLite doesn't support ENUM, convert to VARCHAR
        if ($type === 'ENUM' && $this->driver === 'sqlite') {
            $maxLength = 0;
            foreach ($options['values'] as $value) {
                $maxLength = max($maxLength, strlen($value));
            }
            return "VARCHAR(" . max($maxLength, 50) . ")";
        }

        return match ($type) {
            'VARCHAR' => $length ? "VARCHAR({$length})" : 'VARCHAR(255)',
            'DECIMAL' => "DECIMAL({$precision}, {$scale})",
            'ENUM' => "ENUM('" . implode("','", $options['values']) . "')",
            'CHAR' => $length ? "CHAR({$length})" : 'CHAR(255)',
            'BIGINT' => 'BIGINT',
            'INT' => 'INT',
            'SMALLINT' => 'SMALLINT',
            'TINYINT' => 'TINYINT',
            'TEXT' => 'TEXT',
            'LONGTEXT' => 'LONGTEXT',
            'MEDIUMTEXT' => 'MEDIUMTEXT',
            'DATE' => 'DATE',
            'DATETIME' => 'DATETIME',
            'TIMESTAMP' => 'TIMESTAMP',
            'TIME' => 'TIME',
            'FLOAT' => 'FLOAT',
            'DOUBLE' => 'DOUBLE',
            default => $type,
        };
    }
}

/**
 * Foreign key builder
 */
class ForeignKey
{
    private Blueprint $blueprint;
    private string $column;
    private ?string $references = null;
    private ?string $on = null;
    private ?string $onDelete = null;
    private ?string $onUpdate = null;

    public function __construct(Blueprint $blueprint, string $column)
    {
        $this->blueprint = $blueprint;
        $this->column = $column;
    }

    /**
     * Set referenced column
     */
    public function references(string $column): self
    {
        $this->references = $column;
        return $this;
    }

    /**
     * Set referenced table
     */
    public function on(string $table): self
    {
        $this->on = $table;
        return $this;
    }

    /**
     * Set ON DELETE action
     */
    public function onDelete(string $action): self
    {
        $this->onDelete = $action;
        return $this;
    }

    /**
     * Set ON UPDATE action
     */
    public function onUpdate(string $action): self
    {
        $this->onUpdate = $action;
        return $this;
    }

    /**
     * Add to blueprint
     */
    public function add(): self
    {
        $sql = "FOREIGN KEY ({$this->column}) REFERENCES {$this->on}({$this->references})";
        
        if ($this->onDelete) {
            $sql .= " ON DELETE {$this->onDelete}";
        }
        if ($this->onUpdate) {
            $sql .= " ON UPDATE {$this->onUpdate}";
        }

        $this->blueprint->getColumns()[$this->column]['foreign'] = $sql;
        return $this;
    }
}
