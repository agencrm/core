<?php

// app/Http/Controllers/Api/UserController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\Api\ApiQueryService;
use App\Http\Resources\UserResource;


class UserController extends Controller
{
    // GET /api/users
    // public function index()
    // {
    //     return User::all();
    // }
    public function index(Request $request, ApiQueryService $api)
    {
        $paginator = $api->run(
            User::query(),
            $request,
            ['name', 'email'],
            ['name', 'email', 'created_at']
        );
        
        return response()->json([
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
            'data' => UserResource::collection($paginator->items()),
        ]);
    }

    // GET /api/users/{id}
    public function show($id)
    {
        return User::findOrFail($id);
    }

    // POST /api/users
    public function store(Request $request)
    {
        $user = User::create($request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]));

        return response()->json($user, 201);
    }

    // PUT /api/users/{id}
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $user->update($request->only(['name', 'email']));

        return response()->json($user);
    }

    // DELETE /api/users/{id}
    public function destroy($id)
    {
        User::findOrFail($id)->delete();

        return response()->json(['message' => 'User deleted']);
    }
}
