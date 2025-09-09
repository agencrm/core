<?php
// database/migrations/2025_05_16_120831_fix_label_group_label_columns.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // If the table already exists, assume it’s in the correct shape.
        // (Adding a composite primary key post-hoc is not supported on SQLite.)
        if (!Schema::hasTable('label_group_label')) {
            // Create the final desired schema if somehow still missing
            Schema::create('label_group_label', function (Blueprint $table) {
                $table->unsignedBigInteger('label_id');
                $table->unsignedBigInteger('label_group_id');
                $table->timestamps();

                $table->foreign('label_id')->references('id')->on('labels')->onDelete('cascade');
                $table->foreign('label_group_id')->references('id')->on('label_groups')->onDelete('cascade');

                $table->primary(['label_id', 'label_group_id']);
            });
        }
    }

    public function down(): void
    {
        // No-op on down; leave existing data alone
    }
};
