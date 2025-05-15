<?php

// app/Http/Controllers/Api/ContactController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Models\Label;


class ContactController extends Controller
{
    // public function index(Request $request)
    // {
    //     return ContactResource::collection(Contact::latest()->paginate(15));
    // }

    public function index(Request $request)
    {
        $contacts = Contact::latest()->get(); // you can re-add pagination later
    
        // Grab all label_ids from dynamic fields
        $labelIds = $contacts
            ->map(fn($c) => $c->getField('label_id'))
            ->filter()
            ->unique();
    
        $labels = Label::whereIn('id', $labelIds)->get()->values();
    
        return response()->json([
            'data' => ContactResource::collection($contacts),
            'sideloaded' => [
                'labels' => $labels,
            ],
        ]);
    }

    public function show($id)
    {
        return new ContactResource(Contact::findOrFail($id));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'path' => 'required|string',
            'mime_type' => 'nullable|string',
            'size' => 'nullable|integer',
        ]);

        $contact = Contact::create($validated);

        return new ContactResource($contact);
    }

    public function destroy($id)
    {
        $contact = Contact::findOrFail($id);
        $contact->delete();

        return response()->json(['contact' => 'Contact deleted']);
    }
}
