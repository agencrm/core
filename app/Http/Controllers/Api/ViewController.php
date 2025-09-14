<?php

// app/Http/Controllers/Api/ViewController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ViewResource;
use App\Models\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\Api\ApiQueryService;

class ViewController extends Controller
{
    /**
     * GET /api/views
     * - Uses ApiQueryService (search/sort/paginate)
     * - Returns { data, meta } where data is a ViewResource collection
     */
    public function index(Request $request, ApiQueryService $apiQuery)
    {
        $userId = $request->user()->id;

        // Base scope: only the owner’s views
        $query = View::query()
            ->where('created_by', $userId)
            // Preserve your existing filters
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

        // Apply generic API query features
        $result = $apiQuery
            ->forModel($query)
            ->searchable(['name'])                 // tweak if you want more fields
            ->sortable(['name', 'created_at'])     // tweak sortables as needed
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
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => ['nullable', 'string', 'max:255'],
            'view_type'     => ['required', 'string', Rule::in(['board', 'table', 'gallery'])],
            'viewable_type' => ['nullable', 'string', 'max:255'],
            'viewable_id'   => ['nullable', 'integer'],
            'is_default'    => ['sometimes', 'boolean'],
            'attributes'    => ['sometimes', 'array'], // EAV map
        ]);

        $view = View::create([
            'name'          => $validated['name'] ?? null,
            'view_type'     => $validated['view_type'],
            'created_by'    => $request->user()->id,
            'viewable_type' => $validated['viewable_type'] ?? null,
            'viewable_id'   => $validated['viewable_id'] ?? null,
            'is_default'    => (bool)($validated['is_default'] ?? false),
        ]);

        foreach (($validated['attributes'] ?? []) as $k => $v) {
            $view->setAttr($k, $v);
        }

        return (new ViewResource($view->load('attributes')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT/PATCH /api/views/{id}
     */
    public function update(Request $request, $id)
    {
        $view = View::with('attributes')->findOrFail($id);
        $this->authorizeOwner($view);

        $validated = $request->validate([
            'name'          => ['sometimes', 'nullable', 'string', 'max:255'],
            'view_type'     => ['sometimes', 'string', Rule::in(['board', 'table', 'gallery'])],
            'viewable_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'viewable_id'   => ['sometimes', 'nullable', 'integer'],
            'is_default'    => ['sometimes', 'boolean'],
            'attributes'    => ['sometimes', 'array'],
        ]);

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
