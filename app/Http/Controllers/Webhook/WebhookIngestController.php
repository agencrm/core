<?php

// app/Http/Controllers/Webhook/WebhookIngestController.php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\WebhookHit;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Services\Webhooks\WebhookQueueService;

class WebhookIngestController extends Controller
{
    public function __construct(protected WebhookQueueService $queueService) {}

    public function __invoke(Request $request, ?string $provider = null): JsonResponse
    {
        $raw  = $request->getContent();
        $json = json_decode($raw, true);
        $body = is_array($json) ? $json : ($request->all() ?: []);

        $provider = $provider ?? $request->route('provider') ?? $request->header('X-Webhook-Provider');
        $event    = $request->header('X-Webhook-Event')
                 ?? $request->header('Stripe-Event-Type')
                 ?? $request->header('X-HubSpot-Event')
                 ?? $request->query('event')
                 ?? ($body['event'] ?? null);

        $hit = WebhookHit::create([
            'provider'    => $provider,
            'event'       => $event,
            'payload'     => !empty($body) ? $body : ['raw' => $raw],
            'headers'     => $request->headers->all(),
            'ip'          => $request->ip(),
            'received_at' => now(),
            'job_status'  => 'queued',
        ]);

        Log::info('Webhook ingested', ['id' => $hit->id, 'provider' => $hit->provider, 'event' => $hit->event]);

        // Best-effort normalization; the job will decide outcome if it's not perfect
        $payload = [
            'event' => (string) ($event ?? ''),
            'data'  => (array) ($body['data'] ?? []),
        ];

        $this->queueService->process($payload, $hit->id);

        return response()->json(['ok' => true, 'id' => $hit->id, 'queued' => true]);
    }
}
