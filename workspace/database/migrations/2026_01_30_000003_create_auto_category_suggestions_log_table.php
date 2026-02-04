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
        Schema::create('auto_category_suggestions_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('suggested_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->tinyInteger('confidence_score');
            $table->json('matched_keywords')->nullable();
            $table->enum('source', [
                'rule_exact',
                'rule_fuzzy',
                'learned_keyword',
                'manual_suggestion',
            ]);
            $table->enum('user_action', [
                'accepted',
                'rejected',
                'ignored',
                'overridden',
            ])->nullable();
            $table->timestamp('suggested_at');
            $table->timestamp('action_at')->nullable();
            $table->timestamps();

            // Indexes for efficient querying
            $table->index(['user_id', 'suggested_at']);
            $table->index(['transaction_id']);
            $table->index(['suggested_category_id']);
            $table->index('source');
            $table->index('user_action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_category_suggestions_log');
    }
};
