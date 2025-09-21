<?php

// database/migrations/2025_09_21_000000_create_notes_table.php

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
        Schema::create('notes', function (Blueprint $table) {
            $table->id();

            // Polymorphic relationship
            $table->string('noteable_type');   // e.g. 'App\Models\Task'
            $table->unsignedBigInteger('noteable_id');

            // Author of the note
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Content of the note
            $table->text('body');

            // Optional: for quick filtering/tagging
            $table->string('title')->nullable();
            $table->string('status')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['noteable_type', 'noteable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
