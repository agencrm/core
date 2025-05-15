<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('entity_values', function (Blueprint $table) {
            $table->renameColumn('labelable_type', 'entityable_type');
            $table->renameColumn('labelable_id', 'entityable_id');

            $table->dropIndex(['labelable_type', 'labelable_id']);
            $table->index(['entityable_type', 'entityable_id']);
        });
    }

    public function down(): void
    {
        Schema::table('entity_values', function (Blueprint $table) {
            $table->renameColumn('entityable_type', 'labelable_type');
            $table->renameColumn('entityable_id', 'labelable_id');

            $table->dropIndex(['entityable_type', 'entityable_id']);
            $table->index(['labelable_type', 'labelable_id']);
        });
    }
};
