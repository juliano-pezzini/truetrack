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
        Schema::create('xlsx_column_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->json('mapping_config');
            $table->boolean('is_default')->default(false)->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            // Indexes for efficient querying
            $table->index('user_id');
            $table->index('account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xlsx_column_mappings');
    }
};
