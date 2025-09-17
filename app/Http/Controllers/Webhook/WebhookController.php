<?php

// app/Http/Controllers/Webhook/WebhookController.php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Services\Webhooks\WebhookQueueService;

class WebhookController extends Controller
{
    protected WebhookQueueService $queueService;

    public function __construct(WebhookQueueService $queueService)
    {
        $this->queueService = $queueService;
    }

    public function handle(Request $request): JsonResponse
    {
        Log::info('Webhook hit', ['data' => $request->all()]);

        try {
            $payload = $request->validate([
                'event' => 'required|string',
                'data'  => 'required|array',
            ]);

            if (!isset($payload['event'], $payload['data'])) {
                throw new \InvalidArgumentException('Invalid webhook payload: Missing event or data.');
            }

            $this->queueService->process($payload);

            return response()->json(['success' => true]);

        } catch (ValidationException $e) {
            Log::error('Webhook validation failed', ['errors' => $e->errors()]);

            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
            ], 500);
        }
    }
}
