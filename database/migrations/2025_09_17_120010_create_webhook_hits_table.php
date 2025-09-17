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
        Schema::create('webhook_hits', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->nullable();       // e.g. 'hubspot', 'stripe'
            $table->string('event')->nullable();          // e.g. 'contact.created'
            $table->json('payload');                      // full request body
            $table->json('headers')->nullable();          // useful for sig debugging
            $table->string('ip')->nullable();             // requester IP
            $table->timestamp('received_at')->useCurrent();
            $table->timestamps();

            $table->index(['provider', 'event']);
            $table->index('received_at');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_hits');
    }
};
