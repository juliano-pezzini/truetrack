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
        Schema::table('xlsx_imports', function (Blueprint $table) {
            $table->json('mapping_config')->nullable()->after('column_mapping_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('xlsx_imports', function (Blueprint $table) {
            $table->dropColumn('mapping_config');
        });
    }
};
