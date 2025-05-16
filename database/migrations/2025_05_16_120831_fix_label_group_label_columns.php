<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('label_group_label', function (Blueprint $table) {
            if (!Schema::hasColumn('label_group_label', 'label_id')) {
                $table->unsignedBigInteger('label_id');
                $table->foreign('label_id')->references('id')->on('labels')->onDelete('cascade');
            }

            if (!Schema::hasColumn('label_group_label', 'label_group_id')) {
                $table->unsignedBigInteger('label_group_id');
                $table->foreign('label_group_id')->references('id')->on('label_groups')->onDelete('cascade');
            }

            // Add composite primary key if it doesn't exist
            $table->primary(['label_id', 'label_group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
