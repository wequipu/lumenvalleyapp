<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileUploadController extends Controller
{
    /**
     * Store a newly uploaded file.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->file('file')) {
            $path = $request->file('file')->store('images', 'public');

            return response()->json([
                'message' => 'File uploaded successfully',
                'path' => Storage::url($path),
            ], 201);
        }

        return response()->json(['message' => 'File not found in request.'], 422);
    }
}
