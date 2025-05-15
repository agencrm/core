<?php

// app/Http/Controllers/Api/MessageController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        return MessageResource::collection(Message::latest()->paginate(15));
    }

    public function show($id)
    {
        return new MessageResource(Message::findOrFail($id));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'path' => 'required|string',
            'mime_type' => 'nullable|string',
            'size' => 'nullable|integer',
        ]);

        $message = Message::create($validated);

        return new MessageResource($message);
    }

    public function destroy($id)
    {
        $message = Message::findOrFail($id);
        $message->delete();

        return response()->json(['message' => 'Message deleted']);
    }
}
