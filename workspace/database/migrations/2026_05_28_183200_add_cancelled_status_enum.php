<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create new enum types with 'cancelled' status
        DB::statement("CREATE TYPE ofx_imports_status_new AS ENUM ('pending', 'processing', 'completed', 'failed', 'cancelled')");
        DB::statement("CREATE TYPE xlsx_imports_status_new AS ENUM ('pending', 'processing', 'completed', 'failed', 'cancelled')");

        // Alter ofx_imports table
        DB::statement("ALTER TABLE ofx_imports ALTER COLUMN status TYPE ofx_imports_status_new USING status::text::ofx_imports_status_new");
        DB::statement("DROP TYPE ofx_imports_status");
        DB::statement("ALTER TYPE ofx_imports_status_new RENAME TO ofx_imports_status");

        // Alter xlsx_imports table
        DB::statement("ALTER TABLE xlsx_imports ALTER COLUMN status TYPE xlsx_imports_status_new USING status::text::xlsx_imports_status_new");
        DB::statement("DROP TYPE xlsx_imports_status");
        DB::statement("ALTER TYPE xlsx_imports_status_new RENAME TO xlsx_imports_status");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Create old enum types without 'cancelled'
        DB::statement("CREATE TYPE ofx_imports_status_old AS ENUM ('pending', 'processing', 'completed', 'failed')");
        DB::statement("CREATE TYPE xlsx_imports_status_old AS ENUM ('pending', 'processing', 'completed', 'failed')");

        // Alter tables back (converting any 'cancelled' values to 'failed')
        DB::statement("UPDATE ofx_imports SET status = 'failed' WHERE status = 'cancelled'");
        DB::statement("ALTER TABLE ofx_imports ALTER COLUMN status TYPE ofx_imports_status_old USING status::text::ofx_imports_status_old");
        DB::statement("DROP TYPE ofx_imports_status");
        DB::statement("ALTER TYPE ofx_imports_status_old RENAME TO ofx_imports_status");

        DB::statement("UPDATE xlsx_imports SET status = 'failed' WHERE status = 'cancelled'");
        DB::statement("ALTER TABLE xlsx_imports ALTER COLUMN status TYPE xlsx_imports_status_old USING status::text::xlsx_imports_status_old");
        DB::statement("DROP TYPE xlsx_imports_status");
        DB::statement("ALTER TYPE xlsx_imports_status_old RENAME TO xlsx_imports_status");
    }
};

