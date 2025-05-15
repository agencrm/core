<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('entity_values', function (Blueprint $table) {
            // Rename old columns just in case you want to retain data temporarily
            $table->renameColumn('model', 'labelable_type');
            $table->renameColumn('model_id', 'labelable_id');

            // Update index
            $table->dropIndex(['model', 'model_id']);
            $table->index(['labelable_type', 'labelable_id']);
        });
    }

    public function down(): void
    {
        Schema::table('entity_values', function (Blueprint $table) {
            $table->renameColumn('labelable_type', 'model');
            $table->renameColumn('labelable_id', 'model_id');

            $table->dropIndex(['labelable_type', 'labelable_id']);
            $table->index(['model', 'model_id']);
        });
    }
};
