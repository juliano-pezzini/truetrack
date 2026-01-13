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
        Schema::create('reconciliation_transaction', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('transaction_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamps();

            // Ensure a transaction can only be reconciled once per reconciliation
            $table->unique(['reconciliation_id', 'transaction_id']);

            // Indexes for performance
            $table->index('reconciliation_id');
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reconciliation_transaction');
    }
};
