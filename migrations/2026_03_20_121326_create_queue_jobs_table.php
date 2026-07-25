<?php

declare(strict_types=1);

use Axiom\Migration\Migration;
use Axiom\Migration\Blueprint;

class CreateQueueJobsTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->create('queue_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue', 255)->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
            $table->index(['queue', 'reserved_at']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->drop('queue_jobs');
    }
}