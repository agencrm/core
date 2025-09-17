<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\WebhookHit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookIngestController extends Controller
{
    // POST /webhooks/ingest or /ingest
    public function __invoke(Request $request)
    {
        // Don’t be strict; store first, validate/verify later if needed
        $json = $request->json()->all() ?? [];

        $hit = WebhookHit::create([
            'provider'    => $request->route('provider') ?? $request->input('provider'),
            'event'       => $json['event'] ?? null,
            'payload'     => $json,
            'headers'     => collect($request->headers->all())
                                ->map(fn($v) => is_array($v) ? implode(',', $v) : $v)
                                ->toArray(),
            'ip'          => $request->ip(),
            'received_at' => now(),
        ]);

        Log::info('Webhook stored', ['id' => $hit->id, 'event' => $hit->event]);

        // Return 2xx fast so sender won’t retry
        return response()->json(['ok' => true, 'id' => $hit->id]);
    }
}
