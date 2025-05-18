<?php

// app/Http/Controllers/Api/ContactController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContactResource;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;
use App\Services\Api\ApiQueryService;

use App\Models\Contact;
use App\Models\Label;


class ContactController extends Controller
{


    /**
     * 
     */
    public function index(Request $request, ApiQueryService $apiQuery)
    {
        $query = Contact::latest();

        $result = $apiQuery
            ->forModel($query)
            ->searchable(['first_name', 'last_name', 'email'])
            ->sortable(['name', 'created_at'])
            ->apply();

        return response()->json([
            'data' => ContactResource::collection($result['results']),
            'meta' => $result['meta'],
        ]);
    }


    /**
     * 
     */
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
