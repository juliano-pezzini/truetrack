<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('learned_category_patterns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('keyword');
            $table->integer('occurrence_count')->default(1);
            $table->tinyInteger('confidence_score')->default(50);
            $table->timestamp('first_learned_at');
            $table->timestamp('last_matched_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Unique keyword per user and category
            $table->unique(['user_id', 'keyword', 'category_id']);

            // Indexes for efficient querying
            $table->index(['user_id', 'is_active', 'confidence_score']);
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learned_category_patterns');
    }
};
