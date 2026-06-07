<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EditorUploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'image', 'max:8192'],
        ]);

        $file = $request->file('file');
        if (! $file || ! $file->isValid()) {
            return response()->json(['error' => 'Invalid upload.'], 422);
        }

        $path = $file->store('editor', 'uploads');

        return response()->json([
            'location' => asset('uploads/'.$path),
        ]);
    }
}
