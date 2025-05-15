<?php

// app/Services/Api/ApiQueryService.php

namespace App\Services\Api;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ApiQueryService
{
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
