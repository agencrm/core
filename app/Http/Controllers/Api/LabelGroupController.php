<?php

// app/Http/Controllers/Api/LabelGroupController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LabelGroupResource;
use App\Services\Api\ApiQueryService;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

use App\Models\LabelGroup;

class LabelGroupController extends Controller
{
    /**
     * List label groups with search, sort, pagination
     */
    public function index(Request $request, ApiQueryService $apiQuery)
    {
        $query = LabelGroup::latest();

        $result = $apiQuery
            ->forModel($query)
            ->searchable(['name', 'slug', 'description'])
            ->sortable(['name', 'updated_at', 'created_at'])
            ->apply();

        return response()->json([
            'data' => LabelGroupResource::collection($result['results']),
            'meta' => $result['meta'],
        ]);
    }

    /**
     * Show a single label group
     */
    public function show($id)
    {
        return new LabelGroupResource(LabelGroup::findOrFail($id));
    }

    /**
     * Create a new label group
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', 'unique:label_groups,slug'],
            'description' => ['nullable', 'string'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $labelGroup = LabelGroup::create($validated);

        return new LabelGroupResource($labelGroup);
    }

    /**
     * Update an existing label group
     */
    public function update(Request $request, $id)
    {
        $labelGroup = LabelGroup::findOrFail($id);

        $validated = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'slug'        => ['sometimes', 'string', 'max:255', Rule::unique('label_groups', 'slug')->ignore($labelGroup->id)],
            'description' => ['sometimes', 'nullable', 'string'],
        ]);

        // If slug omitted but name provided, auto-generate from name
        if (!array_key_exists('slug', $validated) && array_key_exists('name', $validated)) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $labelGroup->update($validated);

        return new LabelGroupResource($labelGroup);
    }

    /**
     * Delete a label group
     */
    public function destroy($id)
    {
        $labelGroup = LabelGroup::findOrFail($id);
        $labelGroup->delete();

        return response()->json(['label_group' => 'LabelGroup deleted']);
    }

    /**
     * Legacy: simple paginator (kept for compatibility)
     */
    public function index_V1(Request $request)
    {
        return LabelGroupResource::collection(LabelGroup::latest()->paginate(15));
    }

    /**
     * Legacy: ApiQueryService v2 (kept for compatibility)
     */
    public function index_V2(Request $request, ApiQueryService $apiQuery)
    {
        $query = LabelGroup::latest();

        $result = $apiQuery
            ->forModel($query)
            ->searchable(['name', 'slug', 'description'])
            ->sortable(['name', 'updated_at', 'created_at'])
            ->apply();

        return response()->json([
            'data' => LabelGroupResource::collection($result['results']),
            'meta' => $result['meta'],
        ]);
    }
}
