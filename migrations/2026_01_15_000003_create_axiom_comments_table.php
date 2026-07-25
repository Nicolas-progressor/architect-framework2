<?php

declare(strict_types=1);

use Axiom\Migration\Migration;
use Axiom\Migration\Blueprint;

class CreateAxiomCommentsTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->create('axiom_comments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('post_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
            $table->text('content');
            $table->boolean('approved')->default(false);
            $table->timestamps();
            
            $table->foreign('post_id')
                ->references('id')
                ->on('axiom_posts')
                ->onDelete('CASCADE');
            
            $table->foreign('user_id')
                ->references('id')
                ->on('axiom_users')
                ->onDelete('CASCADE');
            
            $table->index('approved');
            $table->index('post_id');
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->drop('axiom_comments');
    }
}
