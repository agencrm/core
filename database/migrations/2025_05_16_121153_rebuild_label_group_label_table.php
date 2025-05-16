<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('label_group_label');

        Schema::create('label_group_label', function (Blueprint $table) {
            $table->unsignedBigInteger('label_id');
            $table->unsignedBigInteger('label_group_id');

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
