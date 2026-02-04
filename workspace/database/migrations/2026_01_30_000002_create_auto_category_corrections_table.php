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
        Schema::create('auto_category_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('original_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('corrected_category_id')->constrained('categories')->cascadeOnDelete();
            $table->text('description_text');
            $table->enum('correction_type', [
                'auto_to_manual',
                'wrong_auto_choice',
                'missing_category',
                'updated_learned_pattern',
                'confidence_override',
            ]);
            $table->tinyInteger('confidence_at_correction')->default(0);
            $table->timestamp('corrected_at');
            $table->timestamps();

            // Indexes for efficient querying
            $table->index(['user_id', 'corrected_at']);
            $table->index(['transaction_id']);
            $table->index('corrected_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_category_corrections');
    }
};
