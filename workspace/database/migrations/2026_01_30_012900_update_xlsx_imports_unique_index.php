<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('xlsx_imports', function () {
            // Drop the existing unique constraint that blocks multiple failed imports.
            DB::statement('ALTER TABLE xlsx_imports DROP CONSTRAINT IF EXISTS xlsx_imports_hash_account_status_unique');
        });

        DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS xlsx_imports_hash_account_active_unique ON xlsx_imports (file_hash, account_id) WHERE status IN ('pending', 'processing', 'completed')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS xlsx_imports_hash_account_active_unique');

        Schema::table('xlsx_imports', function () {
            DB::statement('ALTER TABLE xlsx_imports ADD CONSTRAINT xlsx_imports_hash_account_status_unique UNIQUE (file_hash, account_id, status)');
        });
    }
};
