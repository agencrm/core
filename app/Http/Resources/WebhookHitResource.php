<?php

// app/Http/Resources/WebhookHitResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

class WebhookHitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'provider'        => $this->provider,
            'event'           => $this->event,
            'ip'              => $this->ip,
            'payload'         => $this->payload,
            'headers'         => $this->headers,
            'received_at'     => optional($this->received_at)->toISOString(),
            'created_at'      => optional($this->created_at)->toISOString(),

            // FLAT FIELDS for your DataTable accessors
            'job_status'      => $this->job_status,       // queued|processing|done
            'job_result'      => $this->job_result,       // success|failed|duplicate|noop
            'job_attempts'    => $this->job_attempts,
            'handler'         => $this->handler,
            'job_id'          => $this->job_id,           // correlation id
            'job_driver_id'   => $this->job_driver_id,    // runtime id if driver exposes it
            'processed_at'    => optional($this->processed_at)->toISOString(),
            'job_response'    => $this->job_response,     // array|text (casted to array in model)

            // OPTIONAL: a nested block if you ever want to consume as a subobject
            'job' => [
                'status'       => $this->job_status,
                'result'       => $this->job_result,
                'attempts'     => $this->job_attempts,
                'handler'      => $this->handler,
                'correlation'  => $this->job_id,
                'driver_id'    => $this->job_driver_id,
                'processed_at' => optional($this->processed_at)->toISOString(),
                'response'     => $this->job_response,
            ],
        ];
    }
}
