<?php
// app/Http/Controllers/Api/FlowController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FlowResource;
use App\Services\Api\ApiQueryService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

use App\Models\Flow;

class FlowController extends Controller
{
    /**
     * List flows with search, sort, pagination
     */
    public function index(Request $request, ApiQueryService $apiQuery)
    {
        $query = Flow::latest();

        $result = $apiQuery
            ->forModel($query)
            ->searchable(['name', 'slug', 'description'])
            ->sortable(['name', 'updated_at', 'created_at'])
            ->apply();

        return response()->json([
            'data' => FlowResource::collection($result['results']),
            'meta' => $result['meta'],
        ]);
    }

    /**
     * Show a single flow
     */
    public function show($id)
    {
        return new FlowResource(Flow::findOrFail($id));
    }

    /**
     * Create a new flow
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', 'unique:flows,slug'],
            'description' => ['nullable', 'string'],
            'graph'       => ['nullable', 'array'],
            'status'      => ['nullable', Rule::in(['draft', 'published', 'archived'])],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $flow = Flow::create($validated);

        return new FlowResource($flow);
    }

    /**
     * Update an existing flow
     */
    public function update(Request $request, $id)
    {
        $flow = Flow::findOrFail($id);

        $validated = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'slug'        => ['sometimes', 'string', 'max:255', Rule::unique('flows', 'slug')->ignore($flow->id)],
            'description' => ['sometimes', 'nullable', 'string'],
            'graph'       => ['sometimes', 'nullable', 'array'],
            'status'      => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
        ]);

        $flow->update($validated);

        return new FlowResource($flow);
    }

    /**
     * Delete a flow
     */
    public function destroy($id)
    {
        $flow = Flow::findOrFail($id);
        $flow->delete();

        return response()->json(['flow' => 'Flow deleted']);
    }
}
