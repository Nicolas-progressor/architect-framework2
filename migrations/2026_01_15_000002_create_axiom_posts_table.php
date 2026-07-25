<?php

declare(strict_types=1);

use Axiom\Migration\Migration;
use Axiom\Migration\Blueprint;

class CreateAxiomPostsTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->create('axiom_posts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->string('title', 255);
            $table->text('content');
            $table->string('slug', 255)->unique();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamps();
            
            $table->foreign('user_id')
                ->references('id')
                ->on('axiom_users')
                ->onDelete('CASCADE');
            
            $table->index('status');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->drop('axiom_posts');
    }
}
