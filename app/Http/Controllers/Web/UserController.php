<?php

// app/Http/Controllers/Web/UserController.php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Show the list of flows.
     */
    public function index()
    {
        $flows = User::latest()->paginate(15);

        return Inertia::render('Users/UserIndex', [
            'flows' => $flows,
        ]);
    }

    /**
     * Show a single flow by id.
     */
    public function show(int $id)
    {
        $flow = User::findOrFail($id);

        return Inertia::render('Users/ShowUser', [
            'flow' => $flow,
        ]);
    }
}
