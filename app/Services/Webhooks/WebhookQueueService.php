<?php

// app/Services/Webhooks/WebhookQueueService.php

namespace App\Services\Webhooks;

use App\Jobs\Webhooks\ProcessWebhookJob;
use App\Models\WebhookHit;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebhookQueueService
{
    public function process(array $validatedData, ?int $webhookHitId = null): void
    {
        Log::info('Received Webhook:', $validatedData);

        $map          = config('webhooks.handlers', []);
        $event        = $validatedData['event'] ?? null;
        $handlerClass = $event ? ($map[$event] ?? null) : null;

        // 1) Pre-generate correlation UUID so we have an immediate ID to show
        $correlationId = (string) Str::uuid();

        if ($webhookHitId) {
            WebhookHit::whereKey($webhookHitId)->update([
                'handler'      => $handlerClass,
                'job_id'       => $correlationId, // our correlation id is set NOW
                'job_status'   => 'queued',
                'job_attempts' => 0,
                'job_result'   => null,
                'job_response' => null,
                'processed_at' => null,
            ]);
        }

        // 2) Dispatch with both the hit id and correlation id
        ProcessWebhookJob::dispatch($validatedData, $webhookHitId, $correlationId);
        // Note: we do NOT have a reliable driver job id here.
    }
}
