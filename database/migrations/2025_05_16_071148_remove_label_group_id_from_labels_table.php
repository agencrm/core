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
        if (!Schema::hasTable('label_group_label')) {
            Schema::create('label_group_label', function (Blueprint $table) {
                $table->foreignId('label_id')->constrained()->cascadeOnDelete();
                $table->foreignId('label_group_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->primary(['label_id', 'label_group_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('label_group_label')) {
            Schema::dropIfExists('label_group_label');
        }
    }
};
