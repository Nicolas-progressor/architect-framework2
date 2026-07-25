<?php

declare(strict_types=1);

namespace Architect\AuthSystem\Console\Commands;

use Architect\AuthSystem\AuthServiceProvider;
use Architect\Core\Console\Command;
use Architect\Core\Console\Input\InputInterface;
use Architect\Core\Console\Output\OutputInterface;

class MakeAuthMigrationCommand extends Command
{
    protected static $defaultName = 'make:auth-migration';
    protected static $defaultDescription = 'Create a migration file for auth system tables based on current configuration';

    protected function configure(): void
    {
        $this->setHelp('Generates a migration file that creates auth_* tables according to the auth configuration.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = AuthServiceProvider::getDefaultConfig();
        $tablePrefix = $config['table_prefix'] ?? 'auth_';

        $timestamp = date('Y_m_d_His');
        $className = "CreateAuthSystemTables{$timestamp}";
        $fileName = "{$timestamp}_create_auth_system_tables.php";

        $migrationContent = $this->generateMigrationContent($tablePrefix, $className);

        $migrationsPath = $this->getMigrationsPath();
        $filePath = $migrationsPath . DIRECTORY_SEPARATOR . $fileName;

        if (file_exists($filePath)) {
            $output->writeln("<error>Migration file already exists: {$fileName}</error>");
            return self::FAILURE;
        }

        if (!is_dir($migrationsPath) && !mkdir($migrationsPath, 0o755, true)) {
            $output->writeln("<error>Cannot create migrations directory: {$migrationsPath}</error>");
            return self::FAILURE;
        }

        file_put_contents($filePath, $migrationContent);
        $output->writeln("<info>Migration created successfully: {$fileName}</info>");
        $output->writeln('<comment>Run migration with: php bin/arc db:migrate</comment>');

        return self::SUCCESS;
    }

    private function generateMigrationContent(string $tablePrefix, string $className): string
    {
        $rolesTable = $tablePrefix . 'roles';
        $usersTable = $tablePrefix . 'users';
        $userOauthTable = $tablePrefix . 'user_oauth';
        $permissionsTable = $tablePrefix . 'permissions';
        $rolePermissionTable = $tablePrefix . 'role_permission';

        return <<<PHP
            <?php

            declare(strict_types=1);

            use Axiom\Migration\Migration;
            use Axiom\Migration\Blueprint;

            class {$className} extends Migration
            {
                /**
                 * Run the migration
                 */
                public function up(): void
                {
                    // Таблица ролей
                    \$this->create('{$rolesTable}', function (Blueprint \$table) {
                        \$table->id();
                        \$table->string('name')->unique();
                        \$table->string('description')->nullable();
                        \$table->json('permissions')->nullable();
                        \$table->timestamps();
                    });

                    // Таблица пользователей
                    \$this->create('{$usersTable}', function (Blueprint \$table) {
                        \$table->id();
                        \$table->string('username')->unique();
                        \$table->string('email')->unique();
                        \$table->string('password');
                        \$table->foreignId('role_id')->nullable()->constrained('{$rolesTable}')->nullOnDelete();
                        \$table->timestamps();
                    });

                    // Таблица связей пользователей с OAuth провайдерами
                    \$this->create('{$userOauthTable}', function (Blueprint \$table) {
                        \$table->id();
                        \$table->foreignId('user_id')->constrained('{$usersTable}')->cascadeOnDelete();
                        \$table->string('provider');
                        \$table->string('provider_id');
                        \$table->json('provider_data')->nullable();
                        \$table->timestamps();
                        \$table->unique(['provider', 'provider_id']);
                        \$table->unique(['user_id', 'provider']);
                    });

                    // Таблица разрешений (опционально)
                    \$this->create('{$permissionsTable}', function (Blueprint \$table) {
                        \$table->id();
                        \$table->string('name')->unique();
                        \$table->string('description')->nullable();
                        \$table->timestamps();
                    });

                    // Связующая таблица роли-разрешения
                    \$this->create('{$rolePermissionTable}', function (Blueprint \$table) {
                        \$table->id();
                        \$table->foreignId('role_id')->constrained('{$rolesTable}')->cascadeOnDelete();
                        \$table->foreignId('permission_id')->constrained('{$permissionsTable}')->cascadeOnDelete();
                        \$table->timestamps();
                        \$table->unique(['role_id', 'permission_id']);
                    });
                }

                /**
                 * Reverse the migration
                 */
                public function down(): void
                {
                    \$this->drop('{$rolePermissionTable}');
                    \$this->drop('{$permissionsTable}');
                    \$this->drop('{$userOauthTable}');
                    \$this->drop('{$usersTable}');
                    \$this->drop('{$rolesTable}');
                }
            }
            PHP;
    }

    private function getMigrationsPath(): string
    {
        // По умолчанию используется общая директория миграций проекта
        $possiblePaths = [
            getcwd() . '/migrations',
            getcwd() . '/database/migrations',
            __DIR__ . '/../../../../migrations',
        ];

        foreach ($possiblePaths as $path) {
            if (is_dir($path)) {
                return $path;
            }
        }

        // Если не найдено, создаём в текущей рабочей директории
        return getcwd() . '/migrations';
    }
}
