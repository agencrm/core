<?php

// app/Http/Controllers/Api/FormController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FormResource;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\Log;
use App\Services\Api\ApiQueryService;

use App\Models\Form;
use App\Models\Label;

class FormController extends Controller
{
    /**
     * GET /api/forms
     */
    public function index(Request $request, ApiQueryService $apiQuery)
    {
        $query = Form::latest();

        $result = $apiQuery
            ->forModel($query)
            ->searchable(['name', 'description', 'slug'])
            ->sortable(['name', 'created_at'])
            ->apply();

        return response()->json([
            'data' => FormResource::collection($result['results']),
            'meta' => $result['meta'],
        ]);
    }

    /**
     * GET /api/forms/{id}
     */
    public function show($id)
    {
        return new FormResource(Form::findOrFail($id));
    }

    /**
     * POST /api/forms
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('forms', 'slug')->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string'],
            'status'      => ['nullable', 'string', 'max:50'], // e.g. draft/published
            'label_id'    => ['nullable', 'integer', 'exists:labels,id'],
            // add other custom fields as needed
        ]);

        // Optional normalization
        if (!empty($validated['slug'])) {
            $validated['slug'] = str()->slug($validated['slug']);
        } else {
            // generate a slug from the name if not provided
            $validated['slug'] = str()->slug($validated['name']);
        }

        $form = Form::create($validated);

        return (new FormResource($form))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * DELETE /api/forms/{id}
     */
    public function destroy($id)
    {
        $form = Form::findOrFail($id);
        $form->delete();

        return response()->json(['message' => 'Form deleted']);
    }
}
