<?php

// app/Http/Controllers/Web/ContactController.php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Inertia\Inertia;

class ContactController extends Controller
{
    /**
     * Show the list of flows.
     */
    public function index()
    {
        $flows = Contact::latest()->paginate(15);

        return Inertia::render('Contacts/ContactIndex', [
            'flows' => $flows,
        ]);
    }

    /**
     * Show a single flow by id.
     */
    public function show(int $id)
    {
        $flow = Contact::findOrFail($id);

        return Inertia::render('Contacts/ShowContact', [
            'flow' => $flow,
        ]);
    }
}
