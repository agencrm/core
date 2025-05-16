<?php

// app/Http/Controllers/Api/LabelGroupController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LabelGroupResource;
use App\Models\LabelGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LabelGroupController extends Controller
{


    /**
     * 
     */
    public function index(Request $request)
    {
        return LabelGroupResource::collection(LabelGroup::latest()->paginate(15));
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
