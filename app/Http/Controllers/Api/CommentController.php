<?php

// app/Http/Controllers/Api/CommentController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\Api\ApiQueryService;

class CommentController extends Controller
{
    /**
     * GET /api/comments
     * Supported query params:
     * - search: string (searches body)
     * - sort: created_at | edited_at
     * - direction: asc|desc
     * - page, limit
     * - commentable_type: FQCN of model (e.g., App\Models\Contact)
     * - commentable_id: integer
     * - user_id: integer
     * - parent_id: integer | 'null' (to fetch only top-level when set to 'null')
     * - with_children: bool (1/0)
     */
    public function index(Request $request, ApiQueryService $apiQuery)
    {
        // Base query + common filters
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
            // parent filter: pass parent_id= null as the literal string 'null' to fetch only top-level
            ->when($request->has('parent_id'), function ($q) use ($request) {
                $param = $request->input('parent_id');
                if ($param === 'null') {
                    $q->whereNull('parent_id');
                } elseif ($param !== null && $param !== '') {
                    $q->where('parent_id', (int) $param);
                }
            });

        // Eager-load children if requested (and their users)
        if ($request->boolean('with_children')) {
            $query->with(['children.user']);
        }

        // Apply shared search/sort/pagination
        $result = $apiQuery
            ->forModel($query)
            ->searchable(['body'])
            ->sortable(['created_at', 'edited_at'])
            ->apply();

        return response()->json([
            'data' => CommentResource::collection($result['results']),
            'meta' => $result['meta'],
        ]);
    }

    /**
     * POST /api/comments
     */
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

        return (new CommentResource($comment))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PATCH /api/comments/{comment}
     */
    public function update(Request $request, Comment $comment)
    {
        // Add policies if you want authorization
        $validated = $request->validate([
            'body' => ['required', 'string'],
            'meta' => ['nullable', 'array'],
        ]);

        $comment->fill($validated);
        $comment->edited_at = now();
        $comment->save();

        $comment->load('user');

        return new CommentResource($comment);
    }

    /**
     * DELETE /api/comments/{comment}
     */
    public function destroy(Request $request, Comment $comment)
    {
        // Add policies if you want authorization
        $comment->delete();
        return response()->noContent();
    }
}
