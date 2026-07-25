<?php

declare(strict_types=1);

use Axiom\Migration\Migration;
use Axiom\Migration\Blueprint;

class CreateAxiomInfo extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        // Drop if exists for clean migration
        $this->dropIfExists('axiom_info');
        
        $this->create('axiom_info', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('version', 50)->default('');
            $table->string('category', 50)->default('core');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
        
        // Insert seed data directly
        $this->getConnection()->query("
            INSERT INTO axiom_info (title, description, version, category, sort_order, created_at, updated_at) VALUES
            ('Query Builder', 'Мощный конструктор запросов с цепочкой методов. Поддерживает SELECT, INSERT, UPDATE, DELETE с полной поддержкой JOIN, подзапросов и агрегатных функций.', '1.0.0', 'core', 1, NOW(), NOW()),
            ('Entity Manager', 'Система работы с сущностями через PHP 8 атрибуты. Автоматическое маппинг данных из БД в объекты, поддержка связей OneToMany, ManyToMany.', '1.0.0', 'core', 2, NOW(), NOW()),
            ('Migrations', 'Управление версиями схемы базы данных. Создание, применение и откат миграций. Поддержка seed данных.', '1.0.0', 'tools', 3, NOW(), NOW()),
            ('Connection Manager', 'Менеджер подключений к БД. Поддержка нескольких подключений, пул соединений, автоматическое переподключение.', '1.0.0', 'core', 4, NOW(), NOW()),
            ('Cache Module', 'Кэширование запросов и результатов. Поддержка различных адаптеров кэширования для повышения производительности.', '1.0.0', 'modules', 5, NOW(), NOW()),
            ('Repository Pattern', 'Реализация паттерна Repository для абстракции доступа к данным. Упрощает тестирование и поддержку кода.', '1.0.0', 'patterns', 6, NOW(), NOW()),
            ('Relations', 'Система связей между сущностями: BelongsTo, HasOne, HasMany, BelongsToMany. Автоматическое построение JOIN запросов.', '1.0.0', 'core', 7, NOW(), NOW()),
            ('Eager Loading', 'Загрузка связанных данных одним запросом. Предотвращает N+1 проблему при работе с коллекциями.', '1.0.0', 'optimization', 8, NOW(), NOW()),
            ('Soft Deletes', 'Мягкое удаление записей. Записи не удаляются физически, а помечаются как удалённые.', '1.0.0', 'features', 9, NOW(), NOW()),
            ('Transactions', 'Поддержка транзакций с автоматическим откатом при ошибках. Методы beginTransaction, commit, rollBack.', '1.0.0', 'core', 10, NOW(), NOW())
        ");
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->drop('axiom_info');
    }
}
