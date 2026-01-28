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
        Schema::create('xlsx_imports', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('file_hash', 64)->index();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reconciliation_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending')->index();
            $table->unsignedInteger('processed_count')->default(0);
            $table->unsignedInteger('total_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('duplicate_count')->default(0);
            $table->text('error_message')->nullable();
            $table->text('error_report_path')->nullable();
            $table->text('file_path');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('column_mapping_id')->nullable()->constrained('xlsx_column_mappings')->nullOnDelete();
            $table->timestamps();

            // Unique index for file-level duplicate detection
            $table->unique(['file_hash', 'account_id', 'status'], 'xlsx_imports_hash_account_status_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xlsx_imports');
    }
};
