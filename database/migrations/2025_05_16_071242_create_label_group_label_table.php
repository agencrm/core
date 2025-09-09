<?php
// database/migrations/2025_05_16_071242_create_label_group_label_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // NO-OP if the table already exists
        if (Schema::hasTable('label_group_label')) {
            return;
        }

        // Create the final desired schema if missing
        Schema::create('label_group_label', function (Blueprint $table) {
            $table->unsignedBigInteger('label_id');
            $table->unsignedBigInteger('label_group_id');
            $table->timestamps();

            $table->foreign('label_id')->references('id')->on('labels')->onDelete('cascade');
            $table->foreign('label_group_id')->references('id')->on('label_groups')->onDelete('cascade');

            $table->primary(['label_id', 'label_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('label_group_label');
    }
};
