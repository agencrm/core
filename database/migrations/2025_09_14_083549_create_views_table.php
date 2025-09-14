<?php

// database/migrations/2025_09_14_000001_create_views_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('views', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            // board | table | gallery | custom...
            $table->string('view_type')->index();

            // Who owns/created the view
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            // Optional: scope this view to a model (e.g., Project, LabelGroup, etc.)
            $table->nullableMorphs('viewable'); // viewable_type, viewable_id

            // Soft default flag within the (user, view_type, viewable) scope
            $table->boolean('is_default')->default(false)->index();

            $table->timestamps();

            // One “default” per scope (db-level enforcement is tricky with morphs;
            // app-level guard recommended).
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('views');
    }
};
