<?php

// app/Http/Controllers/Api/WebhookHitController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WebhookHitResource;
use App\Models\WebhookHit;
use App\Services\Api\ApiQueryService;
use Illuminate\Http\Request;

class WebhookHitController extends Controller
{
    public function index(Request $request, ApiQueryService $apiQuery)
    {
        $query = WebhookHit::query()->latest('received_at');

        $result = $apiQuery
            ->forModel($query)
            ->searchable(['provider', 'event', 'ip'])
            ->sortable(['received_at', 'provider', 'event', 'created_at'])
            ->apply();

        return response()->json([
            'data' => WebhookHitResource::collection($result['results']),
            'meta' => $result['meta'],
        ]);
    }

    public function show($id)
    {
        return new WebhookHitResource(WebhookHit::findOrFail($id));
    }

    public function destroy($id)
    {
        WebhookHit::findOrFail($id)->delete();
        return response()->json(['deleted' => true]);
    }
}
