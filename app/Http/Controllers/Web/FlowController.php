<?php

// app/Http/Controllers/Web/FlowController.php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Flow;
use Inertia\Inertia;

class FlowController extends Controller
{
    /**
     * Show the list of flows.
     */
    public function index()
    {
        $flows = Flow::latest()->paginate(15);

        return Inertia::render('Flows/FlowIndex', [
            'flows' => $flows,
        ]);
    }

    /**
     * Show a single flow by id.
     */
    public function show(int $id)
    {
        $flow = Flow::findOrFail($id);

        return Inertia::render('Flows/ShowFlow', [
            'flow' => $flow,
        ]);
    }
}
