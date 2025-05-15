<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('entity_values', function (Blueprint $table) {
            $table->id();

            $table->string('model'); // e.g. 'Contact'
            $table->unsignedBigInteger('model_id'); // e.g. 15

            $table->enum('type', ['label', 'field', 'relation'])->index(); // what kind of value
            $table->string('key')->nullable(); // optional (e.g., for field or relation)
            $table->text('value')->nullable(); // raw value — ID, text, etc.

            $table->timestamps();

            $table->index(['model', 'model_id']); // scoped indexing
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_values');
    }
};
