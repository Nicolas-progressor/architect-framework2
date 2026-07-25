<?php

declare(strict_types=1);

use Axiom\Migration\Migration;
use Axiom\Migration\Blueprint;

class CreateFailedJobsTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('connection', 255);
            $table->string('queue', 255);
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
            $table->index(['connection', 'queue']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->drop('failed_jobs');
    }
}