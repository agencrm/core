<?php

// app/Jobs/Webhooks/ProcessWebhookJob.php

namespace App\Jobs\Webhooks;

use App\Models\WebhookHit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $payload;
    public ?int $webhookHitId;
    public string $correlationId;

    public int $tries   = 3;
    public int $backoff = 10;

    public function __construct(array $payload, ?int $webhookHitId, string $correlationId)
    {
        $this->payload        = $payload;
        $this->webhookHitId   = $webhookHitId;
        $this->correlationId  = $correlationId;
    }

    public function handle(): void
    {
        if ($hit = $this->hit()) {
            $hit->job_status   = 'processing';
            $hit->job_attempts = $this->attempts();
            if (method_exists($this->job, 'getJobId')) {
                $hit->job_driver_id = $this->job->getJobId();
            }
            if (!$hit->job_id) {
                $hit->job_id = $this->correlationId;
            }
            $hit->save();
        }

        $event = $this->payload['event'] ?? null;
        $data  = $this->payload['data']  ?? null;

        if (!$event || !is_array($data)) {
            $this->finish('failed', ['message' => 'Invalid webhook payload']);
            // If you prefer retries for malformed payloads, rethrow; otherwise return.
            return;
        }

        $map          = config('webhooks.handlers', []);
        $handlerClass = $map[$event] ?? null;

        if (!$handlerClass) {
            $this->finish('success', ['message' => 'No handler (noop)']);
            return;
        }

        try {
            Log::info('Dispatching handler', ['handler' => $handlerClass, 'event' => $event]);
            app($handlerClass)($data);
            Log::info('Handler completed', ['handler' => $handlerClass, 'event' => $event]);

            $this->finish('success');
        } catch (\Throwable $e) {
            // Mark duplicate as success/noop; make detection more robust across drivers
            if ($this->isDuplicateViolation($e)) {
                $this->finish('success', ['message' => 'Duplicate; no-op']);
                return;
            }

            // Always persist failure details so the UI updates, then allow retry if desired
            $this->finish('failed', [
                'error'   => $e->getMessage(),
                'class'   => get_class($e),
                'code'    => method_exists($e, 'getCode') ? (string) $e->getCode() : null,
            ]);

            // If you still want retries, rethrow; if not, return.
            // throw $e;
            return;
        }
    }

    public function failed(\Throwable $e): void
    {
        // Edge case: if the job crashes after handle() without hitting the catch
        $this->finish('failed', [
            'error'   => $e->getMessage(),
            'class'   => get_class($e),
            'code'    => method_exists($e, 'getCode') ? (string) $e->getCode() : null,
        ]);
    }

    private function hit(): ?WebhookHit
    {
        return $this->webhookHitId ? WebhookHit::find($this->webhookHitId) : null;
    }

    private function finish(string $result, array $response = []): void
    {
        if ($hit = $this->hit()) {
            $hit->job_status   = 'done';              // queued|processing|done
            $hit->job_result   = $result;             // success|failed|duplicate|noop
            $hit->job_response = $response ?: null;   // array cast; stored as JSON/text
            $hit->processed_at = now();

            if (method_exists($this->job, 'getJobId') && !$hit->job_driver_id) {
                $hit->job_driver_id = $this->job->getJobId();
            }

            $hit->save();
        }
    }

    private function isDuplicateViolation(\Throwable $e): bool
    {
        $msg = $e->getMessage();

        // SQLite message pattern
        if (str_contains($msg, 'UNIQUE constraint failed')) {
            return true;
        }

        // MySQL/MariaDB: 1062 duplicate key
        if ($e instanceof QueryException && str_contains($msg, 'Integrity constraint violation: 1062')) {
            return true;
        }

        // PostgreSQL: duplicate key value violates unique constraint
        if (str_contains($msg, 'duplicate key value violates unique constraint')) {
            return true;
        }

        // Generic SQLSTATE 23000 (integrity constraint violation) — check common phrases
        if ($e instanceof QueryException && $e->getCode() === '23000' && str_contains($msg, 'duplicate')) {
            return true;
        }

        return false;
    }
}
