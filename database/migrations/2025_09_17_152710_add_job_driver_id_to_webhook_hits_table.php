<?php

// database/migrations/2025_09_17_140001_add_job_driver_id_to_webhook_hits_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('webhook_hits', function (Blueprint $table) {
            // job_id = our correlation UUID (set before dispatch)
            // job_driver_id = the queue driver's ID (set inside the job when available)
            $table->string('job_driver_id')->nullable()->after('job_id');
        });
    }

    public function down(): void
    {
        Schema::table('webhook_hits', function (Blueprint $table) {
            $table->dropColumn('job_driver_id');
        });
    }
};
