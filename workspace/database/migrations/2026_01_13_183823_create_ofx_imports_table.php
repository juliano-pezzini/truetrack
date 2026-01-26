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
        Schema::create('ofx_imports', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('file_hash', 64); // SHA-256 hash
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reconciliation_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->integer('processed_count')->default(0);
            $table->integer('total_count')->default(0);
            $table->text('error_message')->nullable();
            $table->string('file_path');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // Indexes
            $table->index('file_hash');
            $table->index('status');
            $table->index(['account_id', 'status']);
            $table->index('user_id');

            // Unique constraint for duplicate detection
            $table->unique(['file_hash', 'account_id', 'status'], 'unique_hash_account_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ofx_imports');
    }
};
