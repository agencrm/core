<?php

// app/Http/Controllers/Api/FileController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FileResource;
use App\Models\File;
use Illuminate\Http\Request;

class FileController extends Controller
{
    public function index(Request $request)
    {
        return FileResource::collection(File::latest()->paginate(15));
    }

    public function show($id)
    {
        return new FileResource(File::findOrFail($id));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'path' => 'required|string',
            'mime_type' => 'nullable|string',
            'size' => 'nullable|integer',
        ]);

        $file = File::create($validated);

        return new FileResource($file);
    }

    public function destroy($id)
    {
        $file = File::findOrFail($id);
        $file->delete();

        return response()->json(['message' => 'File deleted']);
    }
}
