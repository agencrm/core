<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();

            // Who wrote it
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Polymorphic target: contacts, files, tasks, projects, etc.
            $table->morphs('commentable'); // creates commentable_type + commentable_id (both indexed)

            // Comment content
            $table->text('body');

            // Optional threading
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();

            // Optional metadata (attachments, client hints, editor info, etc.)
            $table->json('meta')->nullable();

            // Track edits
            $table->timestamp('edited_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Helpful composite index for common queries
            $table->index(['commentable_type', 'commentable_id', 'created_at'], 'comments_target_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
