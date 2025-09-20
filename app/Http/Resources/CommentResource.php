<?php
// app/Http/Resources/CommentResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'body'              => $this->body,
            'meta'              => $this->meta,
            'edited_at'         => $this->edited_at?->toIso8601String(),
            'created_at'        => $this->created_at?->toIso8601String(),
            'updated_at'        => $this->updated_at?->toIso8601String(),
            'parent_id'         => $this->parent_id,
            'user'              => [
                'id'   => $this->user?->id,
                'name' => $this->user?->name,
            ],
            'commentable'       => [
                'type' => $this->commentable_type,
                'id'   => $this->commentable_id,
            ],
            // optional descendants (eager load children if you want a tree)
            'children'          => CommentResource::collection($this->whenLoaded('children')),
        ];
    }
}
