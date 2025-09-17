<?php

// app/Http/Controllers/Api/ViewController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ViewResource;
use App\Models\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Services\Api\ApiQueryService;

class ViewController extends Controller
{
    /**
     * GET /api/views
     */
    public function index(Request $request, ApiQueryService $apiQuery)
    {
        $userId = $request->user()->id;

        $query = View::query()
            ->where('created_by', $userId)
            ->when($request->filled('view_type'), fn ($q) =>
                $q->where('view_type', $request->string('view_type'))
            )
            ->when(
                $request->filled('viewable_type') && $request->filled('viewable_id'),
                function ($q) use ($request) {
                    $q->where('viewable_type', $request->string('viewable_type'))
                      ->where('viewable_id',   $request->integer('viewable_id'));
                }
            );

        $result = $apiQuery
            ->forModel($query)
            ->searchable(['name'])
            ->sortable(['name', 'created_at'])
            ->apply();

        return response()->json([
            'data' => ViewResource::collection($result['results']),
            'meta' => $result['meta'],
        ]);
    }

    /**
     * GET /api/views/{id}
     */
    public function show($id)
    {
        $view = View::with('attributes')->findOrFail($id);
        $this->authorizeOwner($view);

        return new ViewResource($view);
    }

    /**
     * POST /api/views
     * Require: non-empty name, valid view_type, non-empty attributes with at least one meaningful key/value.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'          => ['required', 'string', 'max:255'],
            'view_type'     => ['required', 'string', Rule::in(['board', 'table', 'gallery'])],
            'viewable_type' => ['nullable', 'string', 'max:255'],
            'viewable_id'   => ['nullable', 'integer'],
            'is_default'    => ['sometimes', 'boolean'],
            'attributes'    => ['required', 'array', 'min:1'],
        ]);

        // Attributes must be "meaningful": at least one of these must be present and non-empty.
        $validator->after(function ($v) use ($request) {
            $attrs = $request->input('attributes', []);
            $hasMeaningful =
                array_key_exists('group_by_type', $attrs) && $attrs['group_by_type'] !== null && $attrs['group_by_type'] !== '' ||
                (isset($attrs['row_type']) && $attrs['row_type'] !== null && $attrs['row_type'] !== '') ||
                (!empty($attrs['label_groups']) && is_array($attrs['label_groups'])) ||
                (!empty($attrs['selected_items']) && is_array($attrs['selected_items']));

            if (!$hasMeaningful) {
                $v->errors()->add('attributes', 'Attributes must include at least one of: group_by_type, row_type, non-empty label_groups, or non-empty selected_items.');
            }
        });

        $validated = $validator->validate();

        $view = View::create([
            'name'          => trim($validated['name']),
            'view_type'     => $validated['view_type'],
            'created_by'    => $request->user()->id,
            'viewable_type' => $validated['viewable_type'] ?? null,
            'viewable_id'   => $validated['viewable_id'] ?? null,
            'is_default'    => (bool)($validated['is_default'] ?? false),
        ]);

        foreach ($validated['attributes'] as $k => $v) {
            $view->setAttr($k, $v);
        }

        return (new ViewResource($view->load('attributes')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT/PATCH /api/views/{id}
     * If fields are present, enforce same non-empty rules.
     */
    public function update(Request $request, $id)
    {
        $view = View::with('attributes')->findOrFail($id);
        $this->authorizeOwner($view);

        $validator = Validator::make($request->all(), [
            'name'          => ['sometimes', 'required', 'string', 'max:255'],
            'view_type'     => ['sometimes', 'required', 'string', Rule::in(['board', 'table', 'gallery'])],
            'viewable_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'viewable_id'   => ['sometimes', 'nullable', 'integer'],
            'is_default'    => ['sometimes', 'boolean'],
            'attributes'    => ['sometimes', 'required', 'array', 'min:1'],
        ]);

        // If attributes given, enforce "meaningful"
        $validator->after(function ($v) use ($request) {
            if ($request->has('attributes')) {
                $attrs = $request->input('attributes', []);
                $hasMeaningful =
                    array_key_exists('group_by_type', $attrs) && $attrs['group_by_type'] !== null && $attrs['group_by_type'] !== '' ||
                    (isset($attrs['row_type']) && $attrs['row_type'] !== null && $attrs['row_type'] !== '') ||
                    (!empty($attrs['label_groups']) && is_array($attrs['label_groups'])) ||
                    (!empty($attrs['selected_items']) && is_array($attrs['selected_items']));
                if (!$hasMeaningful) {
                    $v->errors()->add('attributes', 'Attributes must include at least one of: group_by_type, row_type, non-empty label_groups, or non-empty selected_items.');
                }
            }
        });

        $validated = $validator->validate();

        // Trim name if present
        if (array_key_exists('name', $validated)) {
            $validated['name'] = trim($validated['name']);
        }

        $view->fill($validated)->save();

        if (array_key_exists('attributes', $validated)) {
            foreach ($validated['attributes'] as $k => $v) {
                $view->setAttr($k, $v);
            }
        }

        return new ViewResource($view->load('attributes'));
    }

    /**
     * DELETE /api/views/{id}
     */
    public function destroy(Request $request, $id)
    {
        $view = View::findOrFail($id);
        $this->authorizeOwner($view);

        $view->delete();

        return response()->json(['deleted' => true]);
    }

    private function authorizeOwner(View $view): void
    {
        if ($view->created_by !== request()->user()->id) {
            abort(403);
        }
    }
}
