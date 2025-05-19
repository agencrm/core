<?php

// app/Http/Controllers/Api/LabelGroupController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LabelGroupResource;

use Illuminate\Support\Facades\Log;
use App\Services\Api\ApiQueryService;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Models\LabelGroup;

class LabelGroupController extends Controller
{


    /**
     * 
     */
    public function index_V1(Request $request)
    {
        return LabelGroupResource::collection(LabelGroup::latest()->paginate(15));
    }


    public function index_V2(Request $request, ApiQueryService $apiQuery)
    {
        $query = LabelGroup::latest();

        $result = $apiQuery
            ->forModel($query)
            ->searchable(['name'])
            ->sortable(['name', 'created_at'])
            ->apply();

        return response()->json([
            'data' => LabelGroupResource::collection($result['results']),
            'meta' => $result['meta'],
        ]);
    } 

    /**
     * 
     */
    public function index(Request $request, ApiQueryService $apiQuery)
    {
    
        $query = LabelGroup::latest();

        $result = $apiQuery
            ->forModel($query)
            ->searchable(['name'])
            ->sortable(['name', 'created_at'])
            ->apply();

        return response()->json([
            'data' => LabelGroupResource::collection($result['results']),
            'meta' => $result['meta'],
        ]);
    }


    /**
     * 
     */
    public function show($id)
    {
        return new LabelGroupResource(LabelGroup::findOrFail($id));
    }


    /**
     * 
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $label = LabelGroup::create($validated);

        return new LabelGroupResource($label);
    }


    /**
     * 
     */
    public function update(Request $request, $id)
    {
        $label = LabelGroup::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:label_groups,slug,' . $label->id,
            'description' => 'nullable|string',
        ]);

        // If slug is not provided but name is, auto-generate it
        if (!isset($validated['slug']) && isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $label->update($validated);

        return new LabelGroupResource($label);
    }


    /**
     * 
     */
    public function destroy($id)
    {
        $label = LabelGroup::findOrFail($id);
        $label->delete();

        return response()->json(['label' => 'LabelGroup deleted']);
    }

}
