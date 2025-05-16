<?php

// app/Http/Controllers/Api/LabelController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LabelResource;
use App\Models\Label;
use Illuminate\Http\Request;

class LabelController extends Controller
{


    public function index(Request $request)
    {
        // ✅ Validate query params
        $validated = $request->validate([
            'search'    => 'nullable|string',
            'sort'      => 'nullable|in:name,description,color,created_at,sort_order',
            'direction' => 'nullable|in:asc,desc',
            'perPage'   => 'nullable|integer|min:1|max:100',
            'page'      => 'nullable|integer|min:1',
        ]);

        $query = Label::with('groups');

        // 🔍 Apply search filter
        if (!empty($validated['search'])) {
            $query->where(function ($q) use ($validated) {
                $q->where('name', 'like', '%' . $validated['search'] . '%')
                ->orWhere('description', 'like', '%' . $validated['search'] . '%');
            });
        }

        // 📊 Apply sorting if specified
        if (!empty($validated['sort'])) {
            $direction = $validated['direction'] ?? 'asc';
            $query->orderBy($validated['sort'], $direction);
        } else {
            $query->latest(); // default sort
        }

        // 📄 Use perPage from validated input or default to 15
        $perPage = $validated['perPage'] ?? 15;

        return LabelResource::collection($query->paginate($perPage));
    }


    public function show($id)
    {
        return new LabelResource(Label::findOrFail($id));
    }


    /**
     * 
     */
    public function store(Request $request)
    {
        \Log::info('LabelController@store called', [
            'raw_input' => $request->all()
        ]);

        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'color'            => 'nullable|string|max:32',
            'sort_order'       => 'nullable|integer',
            'label_group_ids'  => 'nullable|array',
            'label_group_ids.*'=> 'exists:label_groups,id',
        ]);

        \Log::info('Validation passed', [
            'validated_data' => $validated
        ]);

        $label = Label::create(collect($validated)->except('label_group_ids')->toArray());

        \Log::info('Label created', [
            'label_id' => $label->id,
            'label_data' => $label->toArray()
        ]);

        if (!empty($validated['label_group_ids'])) {
            \Log::info('Syncing label groups', [
                'label_id' => $label->id,
                'group_ids' => $validated['label_group_ids']
            ]);

            $label->groups()->sync($validated['label_group_ids']);
        } else {
            \Log::info('No label_group_ids to sync');
        }

        $label->load('groups');

        \Log::info('Returning LabelResource');

        return new LabelResource($label);
    }




    /**
     * 
     */
    public function update(Request $request, $id)
    {
        $label = Label::findOrFail($id);

        $validated = $request->validate([
            'name'             => 'sometimes|required|string|max:255',
            'description'      => 'nullable|string',
            'color'            => 'nullable|string|max:32',
            'sort_order'       => 'nullable|integer',
            'label_group_ids'  => 'nullable|array',
            'label_group_ids.*'=> 'exists:label_groups,id',
        ]);

        \Log::info('Updating label', ['label_id' => $label->id, 'validated_data' => $validated]);

        $label->update(collect($validated)->except('label_group_ids')->toArray());

        if (array_key_exists('label_group_ids', $validated)) {
            \Log::info('Syncing label groups', [
                'label_id' => $label->id,
                'group_ids' => $validated['label_group_ids']
            ]);

            $label->groups()->sync($validated['label_group_ids']);
        } else {
            \Log::info('No label_group_ids present in request, skipping sync.');
        }

        $label->load('groups');

        return new LabelResource($label);
    }


    /**
     * 
     */
    public function destroy($id)
    {
        $label = Label::findOrFail($id);
        $label->delete();

        return response()->json(['label' => 'Label deleted']);
    }
}
