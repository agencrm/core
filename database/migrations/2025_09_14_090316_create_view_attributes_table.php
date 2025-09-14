<?php

// database/migrations/2025_09_14_090316_create_view_attributes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('view_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('view_id')->constrained('views')->cascadeOnDelete();
            $table->string('key'); // e.g., group_by_type, label_groups, selected_items
            $table->json('value_json')->nullable(); // json for sqlite compatibility
            $table->string('value_string')->nullable();
            $table->bigInteger('value_number')->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->timestamps();

            $table->unique(['view_id', 'key']);
            $table->index('key');
        });

        // Add a GIN index only on PostgreSQL
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX IF NOT EXISTS view_attributes_value_json_gin ON view_attributes USING GIN ((value_json) jsonb_path_ops)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // Safe drop; index names are global in PG
            DB::statement('DROP INDEX IF EXISTS view_attributes_value_json_gin');
        }

        Schema::dropIfExists('view_attributes');
    }
};
