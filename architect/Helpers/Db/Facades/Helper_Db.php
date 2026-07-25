<?php

declare(strict_types=1);

namespace Architect\Helpers\Db\Facades;

use Architect\Helpers\Core\Facade;

/**
 * Facade for Database helper.
 *
 * @method static \PDOStatement query(string $sql, array $bindings = [])
 * @method static int execute(string $sql, array $bindings = [])
 * @method static array|null fetch(string $sql, array $bindings = [])
 * @method static array fetchAll(string $sql, array $bindings = [])
 * @method static \PDO getPdo()
 * @method static bool beginTransaction()
 * @method static bool commit()
 * @method static bool rollBack()
 * @method static bool inTransaction()
 * @method static mixed transaction(callable $callback)
 * @method static string|false lastInsertId(?string $sequence = null)
 * @method static \Architect\Services\Database\Database connectionName(string $name)
 * @method static string getConnectionName()
 */
class Helper_Db extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'db';
    }
}