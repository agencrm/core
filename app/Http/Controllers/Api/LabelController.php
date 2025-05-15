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
        return LabelResource::collection(Label::latest()->paginate(15));
    }

    public function show($id)
    {
        return new LabelResource(Label::findOrFail($id));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            //'label_group_id' => 'required|exists:label_groups,id',
            'label_group_id' => 'nullable|exists:label_groups,id',
            'name'           => 'required|string|max:255',
            'color'          => 'nullable|string|max:32', // you can tweak this depending on format
            'sort_order'     => 'nullable|integer',
        ]);

        $label = Label::create($validated);

        return new LabelResource($label);
    }

    public function update(Request $request, $id)
    {
        $label = Label::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'color'       => 'nullable|string|max:32',
            'label_group_id' => 'nullable|exists:label_groups,id',
            'sort_order'  => 'nullable|integer',
        ]);

        $label->update($validated);

        return new LabelResource($label);
    }


    public function destroy($id)
    {
        $label = Label::findOrFail($id);
        $label->delete();

        return response()->json(['label' => 'Label deleted']);
    }
}
