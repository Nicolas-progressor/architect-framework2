<?php

declare(strict_types=1);

use Axiom\Migration\Migration;
use Axiom\Migration\Blueprint;

class CreateAuthSystemTables extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        // Таблица ролей уже создана предыдущей частью миграции, пропускаем создание

        // Таблица пользователей
        $this->create('auth_users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->integer('role_id')->unsigned()->nullable();
            $table->timestamps();
            // Внешний ключ
            $table->foreign('role_id')->references('id')->on('auth_roles')->onDelete('SET NULL')->add();
        });

        // Таблица связей пользователей с OAuth провайдерами
        $this->create('auth_user_oauth', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->unsigned();
            $table->string('provider'); // google, github и т.д.
            $table->string('provider_id'); // ID пользователя у провайдера
            $table->json('provider_data')->nullable(); // Дополнительные данные
            $table->timestamps();
            $table->unique(['provider', 'provider_id']);
            $table->unique(['user_id', 'provider']);
            // Внешний ключ
            $table->foreign('user_id')->references('id')->on('auth_users')->onDelete('CASCADE')->add();
        });

        // Таблица разрешений (опционально, если нужна отдельная таблица)
        $this->create('auth_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Связующая таблица роли-разрешения (многие ко многим)
        $this->create('auth_role_permission', function (Blueprint $table) {
            $table->id();
            $table->integer('role_id')->unsigned();
            $table->integer('permission_id')->unsigned();
            $table->timestamps();
            $table->unique(['role_id', 'permission_id']);
            // Внешние ключи
            $table->foreign('role_id')->references('id')->on('auth_roles')->onDelete('CASCADE')->add();
            $table->foreign('permission_id')->references('id')->on('auth_permissions')->onDelete('CASCADE')->add();
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->drop('auth_role_permission');
        $this->drop('auth_permissions');
        $this->drop('auth_user_oauth');
        $this->drop('auth_users');
        $this->drop('auth_roles');
    }
}