<?php
// app/Webhooks/Handlers/ContactCreated.php

namespace App\Webhooks\Handlers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ContactController;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Throwable;

class ContactCreated
{
    /**
     * @param array<string,mixed> $data
     */
    public function __invoke(array $data): void
    {
        Log::info('ContactCreated handler', ['data' => $data]);

        [$first, $last] = $this->splitName(
            $data['first_name'] ?? null,
            $data['last_name']  ?? null,
            $data['name']       ?? null
        );

        $requestData = [
            'first_name' => $first,
            'last_name'  => $last,
            'email'      => $data['email'] ?? null,
        ];
        if (!empty($data['label_id'])) {
            $requestData['label_id'] = $data['label_id'];
        }

        try {
            Log::info('Calling ContactController@store', ['request' => $requestData]);
            $response = app(ContactController::class)->store(new Request($requestData));
            Log::info('ContactController@store success', [
                'status'   => $response->getStatusCode(),
                'response' => method_exists($response, 'getContent') ? $response->getContent() : null,
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation failed creating contact', [
                'errors'  => $e->errors(),
                'request' => $requestData,
            ]);
            // decide: rethrow to retry or swallow as "already exists"?
            // throw $e;
        } catch (MassAssignmentException $e) {
            Log::error('Mass assignment error (check Contact::$fillable)', [
                'error'   => $e->getMessage(),
                'request' => $requestData,
            ]);
            throw $e;
        } catch (Throwable $e) {
            Log::error('Unexpected error creating contact', [
                'error'   => $e->getMessage(),
                'request' => $requestData,
                'trace'   => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    private function splitName(?string $first, ?string $last, ?string $full): array
    {
        if ($first) return [$first, $last ?? ''];
        $full = trim((string) $full);
        if ($full === '') return ['Unknown', ''];
        $parts = preg_split('/\s+/', $full, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($parts) === 1) return [$parts[0], ''];
        $f = array_shift($parts);
        return [$f, implode(' ', $parts)];
    }
}
