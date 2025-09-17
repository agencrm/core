<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Make columns NOT NULL (Laravel will rebuild table for SQLite as needed)
        Schema::table('views', function (Blueprint $table) {
            $table->string('name', 255)->nullable(false)->change();
            $table->string('view_type', 32)->nullable(false)->change();
        });

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // 2) Enforce with triggers (SQLite cannot ADD CONSTRAINT on existing tables)
            // Name not empty (trimmed)
            DB::unprepared("
                CREATE TRIGGER IF NOT EXISTS trg_views_name_not_empty_insert
                BEFORE INSERT ON views
                FOR EACH ROW
                BEGIN
                    SELECT CASE
                        WHEN LENGTH(TRIM(NEW.name)) = 0 THEN
                            RAISE(ABORT, 'View name cannot be empty')
                    END;
                END;
            ");

            DB::unprepared("
                CREATE TRIGGER IF NOT EXISTS trg_views_name_not_empty_update
                BEFORE UPDATE OF name ON views
                FOR EACH ROW
                BEGIN
                    SELECT CASE
                        WHEN LENGTH(TRIM(NEW.name)) = 0 THEN
                            RAISE(ABORT, 'View name cannot be empty')
                    END;
                END;
            ");

            // view_type in allowed set
            DB::unprepared("
                CREATE TRIGGER IF NOT EXISTS trg_views_type_allowed_insert
                BEFORE INSERT ON views
                FOR EACH ROW
                BEGIN
                    SELECT CASE
                        WHEN NEW.view_type NOT IN ('board','table','gallery') THEN
                            RAISE(ABORT, 'Invalid view_type')
                    END;
                END;
            ");

            DB::unprepared("
                CREATE TRIGGER IF NOT EXISTS trg_views_type_allowed_update
                BEFORE UPDATE OF view_type ON views
                FOR EACH ROW
                BEGIN
                    SELECT CASE
                        WHEN NEW.view_type NOT IN ('board','table','gallery') THEN
                            RAISE(ABORT, 'Invalid view_type')
                    END;
                END;
            ");
        } else {
            // 2) Real CHECK constraints for databases that support them
            // Postgres & MySQL 8.0.16+ handle this fine.
            try {
                DB::statement("ALTER TABLE views ADD CONSTRAINT chk_views_name_not_empty CHECK (CHAR_LENGTH(TRIM(name)) > 0)");
            } catch (\Throwable $e) {
                // ignore if already exists / unsupported
            }

            try {
                DB::statement("ALTER TABLE views ADD CONSTRAINT chk_views_view_type CHECK (view_type IN ('board','table','gallery'))");
            } catch (\Throwable $e) {
                // ignore if already exists / unsupported
            }
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::unprepared("DROP TRIGGER IF EXISTS trg_views_name_not_empty_insert;");
            DB::unprepared("DROP TRIGGER IF EXISTS trg_views_name_not_empty_update;");
            DB::unprepared("DROP TRIGGER IF EXISTS trg_views_type_allowed_insert;");
            DB::unprepared("DROP TRIGGER IF EXISTS trg_views_type_allowed_update;");
        } else {
            // Drop CHECK constraints where supported (names must match those used in up())
            try {
                // Postgres
                DB::statement("ALTER TABLE views DROP CONSTRAINT IF EXISTS chk_views_name_not_empty");
            } catch (\Throwable $e) {}
            try {
                DB::statement("ALTER TABLE views DROP CONSTRAINT IF EXISTS chk_views_view_type");
            } catch (\Throwable $e) {}

            // MySQL fallback (older versions may not support DROP CONSTRAINT syntax)
            try {
                DB::statement("ALTER TABLE views DROP CHECK chk_views_name_not_empty");
            } catch (\Throwable $e) {}
            try {
                DB::statement("ALTER TABLE views DROP CHECK chk_views_view_type");
            } catch (\Throwable $e) {}
        }

        // Optionally relax NOT NULL back (only if you truly want to revert)
        Schema::table('views', function (Blueprint $table) {
            $table->string('name', 255)->nullable()->change();
            $table->string('view_type', 32)->nullable()->change();
        });
    }
};
