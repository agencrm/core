<?php

// app/Services/Api/ApiQueryService.php

namespace App\Services\Api;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ApiQueryService
{

    protected Builder $query;
    protected Request $request;
    protected array $searchable = [];
    protected array $sortable = [];

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function forModel(Builder $query): static
    {
        $this->query = $query;
        return $this;
    }

    public function searchable(array $fields): static
    {
        $this->searchable = $fields;
        return $this;
    }

    public function sortable(array $fields): static
    {
        $this->sortable = $fields;
        return $this;
    }

    public function apply(): array
    {
        // Search
        if ($this->request->filled('search') && count($this->searchable)) {
            $search = $this->request->input('search');
            $this->query->where(function ($q) use ($search) {
                foreach ($this->searchable as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        // Sort
        $sort = $this->request->input('sort');
        $direction = $this->request->input('direction', 'asc');

        if (in_array($sort, $this->sortable)) {
            $this->query->orderBy($sort, $direction);
        } else {
            $this->query->latest();
        }

        // Pagination
        $page = $this->request->integer('page', 1);
        $limit = $this->request->integer('limit', 15);
        $offset = ($page - 1) * $limit;

        $total = (clone $this->query)->getQuery()->getCountForPagination();
        $results = $this->query->offset($offset)->limit($limit)->get();
        $count = $results->count();

        return [
            'results' => $results,
            'meta' => [
                'total'        => $total,
                'limit'        => $limit,
                'offset'       => $offset,
                'page'         => $page,
                'next'         => $results->last()?->id,
                'has_more'     => ($offset + $count) < $total,
                'current_page' => $page,
                'last_page'    => ceil($total / $limit),
            ]
        ];
    }




    public function run(
        Builder $query,
        Request $request,
        array $searchable = [],
        array $sortable = [],
        int $defaultPerPage = 15
    ) {
        // Global search
        if ($request->filled('search') && !empty($searchable)) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search, $searchable) {
                foreach ($searchable as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        // Sorting
        $sort = $request->input('sort');
        $dir  = $request->input('direction', 'asc');

        if ($sort && in_array($sort, $sortable)) {
            $query->orderBy($sort, $dir);
        }

        // Pagination
        $perPage = $request->input('per_page', $defaultPerPage);

        return $query->paginate($perPage)->appends($request->query());
    }
}
