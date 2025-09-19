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
        Schema::create('forms', function (Blueprint $table) {
            $table->id();

            $table->string('name');                       // required
            $table->string('slug')->unique();             // used for URL-safe identifiers
            $table->text('description')->nullable();      // optional
            $table->string('status', 50)->nullable();     // e.g. draft, published

            $table->unsignedBigInteger('label_id')->nullable(); // optional label
            $table->foreign('label_id')->references('id')->on('labels')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes(); // recommended if you want to use unique rules with deleted_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
