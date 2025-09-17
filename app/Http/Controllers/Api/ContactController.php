<?php

// app/Http/Controllers/Api/ContactController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContactResource;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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


    /**
     * 
     */
    public function store(Request $request)
    {
        // Use DNS checks only in production to avoid local/test payload failures (e.g. example.com)
        $emailRule = app()->environment('production') ? 'email:rfc,dns' : 'email:rfc';

        $validated = $request->validate([
            'first_name' => ['nullable', 'string', 'max:255'],      // FIX: required by DB
            'last_name'  => ['nullable', 'string', 'max:255'],
            'email'      => [
                'nullable',
                'string',
                $emailRule,
                'max:255',
                Rule::unique('contacts', 'email')->whereNull('deleted_at'), // ok if you add soft deletes later
            ],
            'label_id'   => ['nullable', 'integer', 'exists:labels,id'],
        ]);

        // Optional normalization
        if (!empty($validated['email'])) {
            $validated['email'] = mb_strtolower($validated['email']);
        }

        $contact = Contact::create($validated);

        return (new ContactResource($contact))
            ->response()
            ->setStatusCode(201);
    }


    /**
     * 
     */
    public function destroy($id)
    {
        $contact = Contact::findOrFail($id);
        $contact->delete();

        return response()->json(['contact' => 'Contact deleted']);
    }
}
