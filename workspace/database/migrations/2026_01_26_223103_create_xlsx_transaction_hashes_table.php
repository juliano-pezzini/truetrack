<?php

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
        Schema::create('xlsx_transaction_hashes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('row_hash', 64)->index();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('imported_at');

            // Unique index to prevent duplicate imports of same transaction
            $table->unique(['user_id', 'account_id', 'row_hash'], 'xlsx_hashes_user_account_hash_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xlsx_transaction_hashes');
    }
};
