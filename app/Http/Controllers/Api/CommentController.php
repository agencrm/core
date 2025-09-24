<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\Api\ApiQueryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    public function index(Request $request, ApiQueryService $apiQuery)
    {
        $query = Comment::query()
            ->with('user')
            ->when($request->filled('commentable_type'), fn ($q) =>
                $q->where('commentable_type', $request->string('commentable_type'))
            )
            ->when($request->filled('commentable_id'), fn ($q) =>
                $q->where('commentable_id', $request->integer('commentable_id'))
            )
            ->when($request->filled('user_id'), fn ($q) =>
                $q->where('user_id', $request->integer('user_id'))
            )
            ->when($request->has('parent_id'), function ($q) use ($request) {
                $param = $request->input('parent_id');
                if ($param === 'null') $q->whereNull('parent_id');
                elseif ($param !== null && $param !== '') $q->where('parent_id', (int) $param);
            });

        if ($request->boolean('with_children')) {
            $query->with(['children.user']);
        }

        $result = $apiQuery
            ->forModel($query)
            ->searchable(['body'])
            ->sortable(['created_at', 'edited_at'])
            ->apply();

        // 🔹 Build one parents map (1 query per type). No morphs, no row-by-row work.
        $parents = $this->sideloadParents(collect($result['results']));

        return response()->json([
            'data'     => CommentResource::collection($result['results']),
            'meta'     => $result['meta'],
            'included' => ['parents' => $parents],
        ]);
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'commentable_type' => ['required', 'string'],
            'commentable_id'   => ['required', 'integer'],
            'body'             => ['required', 'string'],
            'parent_id'        => ['nullable', Rule::exists('comments', 'id')],
            'meta'             => ['nullable', 'array'],
        ]);

        $comment = Comment::create([
            'user_id'          => $request->user()->id,
            'commentable_type' => $payload['commentable_type'],
            'commentable_id'   => $payload['commentable_id'],
            'body'             => $payload['body'],
            'parent_id'        => $payload['parent_id'] ?? null,
            'meta'             => $payload['meta'] ?? null,
        ])->load('user');

        // Also sideload the single parent for convenience
        $parents = $this->sideloadParents(collect([$comment]));

        return (new CommentResource($comment))
            ->additional(['included' => ['parents' => $parents]])
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Comment $comment)
    {
        $validated = $request->validate([
            'body' => ['required', 'string'],
            'meta' => ['nullable', 'array'],
        ]);

        $comment->fill($validated);
        $comment->edited_at = now();
        $comment->save();
        $comment->load('user');

        $parents = $this->sideloadParents(collect([$comment]));

        return (new CommentResource($comment))
            ->additional(['included' => ['parents' => $parents]]);
    }

    /**
     * Build a flat parents map keyed by "type:id" => ['label' => ..., 'data' => ...]
     * Complexity: O(T + Σ queries per type). DB hits are minimized (one per type).
     */
    private function sideloadParents(Collection $comments): array
    {
        if ($comments->isEmpty()) return [];

        // 1) Collect unique IDs per type (cheap in PHP)
        $byType = [];
        foreach ($comments as $c) {
            $type = (string) $c->commentable_type;
            $id   = (int) $c->commentable_id;
            if (!$type || !$id) continue;
            $byType[$type][$id] = true;
        }

        // 2) Query each type once, compute labels in SQL where reasonable
        $out = [];

        // Entities
        if (!empty($byType['App\\Models\\Entity'])) {
            $ids = array_keys($byType['App\\Models\\Entity']);
            $rows = DB::table('entities')->select('id', 'name')->whereIn('id', $ids)->get();
            foreach ($rows as $r) {
                $key = "App\\Models\\Entity:{$r->id}";
                $out[$key] = [
                    'label' => $r->name ?: "Entity #{$r->id}",
                    'data'  => ['id' => $r->id, 'name' => $r->name],
                ];
            }
        }

        // Contacts (Postgres-friendly CASE for label)
        if (!empty($byType['App\\Models\\Contact'])) {
            $ids = array_keys($byType['App\\Models\\Contact']);
            $rows = DB::table('contacts')
                ->select([
                    'id',
                    'first_name',
                    'last_name',
                    'name',
                    'company',
                    'email',
                    DB::raw("
                        CASE
                            WHEN trim(coalesce(first_name,'') || ' ' || coalesce(last_name,'')) <> ''
                                THEN trim(coalesce(first_name,'') || ' ' || coalesce(last_name,''))
                            WHEN name IS NOT NULL THEN name
                            WHEN company IS NOT NULL THEN company
                            ELSE 'Contact #' || id::text
                        END AS label
                    "),
                ])
                ->whereIn('id', $ids)
                ->get();

            foreach ($rows as $r) {
                $key = "App\\Models\\Contact:{$r->id}";
                $out[$key] = [
                    'label' => $r->label,
                    'data'  => [
                        'id'         => $r->id,
                        'first_name' => $r->first_name,
                        'last_name'  => $r->last_name,
                        'name'       => $r->name,
                        'company'    => $r->company,
                        'email'      => $r->email,
                    ],
                ];
            }
        }

        // Other types? Add a single small block per type (still 1 query per type).
        // if (!empty($byType['App\\Models\\Project'])) { ... }

        // 3) Generic fallback for unknown types (no DB call)
        foreach ($byType as $type => $idsAssoc) {
            if (isset($out[$type.':*'])) continue; // not used; just illustrative
            if ($type === 'App\\Models\\Entity' || $type === 'App\\Models\\Contact') continue;
            foreach (array_keys($idsAssoc) as $id) {
                $out["{$type}:{$id}"] = [
                    'label' => class_basename($type) . " #{$id}",
                    'data'  => ['id' => $id],
                ];
            }
        }

        return $out;
    }
}
