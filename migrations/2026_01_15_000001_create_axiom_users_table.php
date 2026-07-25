<?php

declare(strict_types=1);

use Axiom\Migration\Migration;
use Axiom\Migration\Blueprint;

class CreateAxiomUsersTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->create('axiom_users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->string('password', 255)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->drop('axiom_users');
    }
}
