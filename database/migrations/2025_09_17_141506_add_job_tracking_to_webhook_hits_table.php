<?php

// database/migrations/2025_09_17_131700_add_job_tracking_to_webhook_hits_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_hits', function (Blueprint $table) {
            $table->string('job_id')->nullable()->after('received_at');
            $table->string('job_status')->default('queued')->after('job_id'); // queued|processing|done
            $table->unsignedInteger('job_attempts')->default(0)->after('job_status');
            $table->string('handler')->nullable()->after('job_attempts');
            $table->timestamp('processed_at')->nullable()->after('handler');
            $table->string('job_result')->nullable()->after('processed_at'); // success|failed|duplicate|noop
            $table->text('job_response')->nullable()->after('job_result');   // message / context / error
        });
    }

    public function down(): void
    {
        Schema::table('webhook_hits', function (Blueprint $table) {
            $table->dropColumn([
                'job_id',
                'job_status',
                'job_attempts',
                'handler',
                'processed_at',
                'job_result',
                'job_response',
            ]);
        });
    }
};
