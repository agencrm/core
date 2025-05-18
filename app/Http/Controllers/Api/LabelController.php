<?php

// app/Http/Controllers/Api/LabelController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LabelResource;
use App\Models\Label;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\Api\ApiQueryService;

class LabelController extends Controller
{

    /**
     * 
     */
    public function index(Request $request, ApiQueryService $apiQuery)
    {
        $query = Label::with('groups');

        $result = $apiQuery
            ->forModel($query)
            ->searchable(['name', 'description'])
            ->sortable(['name', 'description', 'color', 'created_at', 'sort_order'])
            ->apply();

        return response()->json([
            'data' => LabelResource::collection($result['results']),
            'meta' => $result['meta'],
        ]);
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
