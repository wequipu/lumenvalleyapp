<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientIdentityController extends Controller
{
    public function store(Request $request, Client $client)
    {
        $validated = $request->validate([
            'id_type' => 'required|string|in:cni,passport,driving_license,resident_permit',
            'id_number' => 'required|string|max:255',
            'id_photo' => 'required|image|max:2048', //     5MB Max
        ]);

        $client->id_type = $validated['id_type'];
        $client->id_number = $validated['id_number'];

        if ($request->hasFile('id_photo')) {
            // Store the new photo
            $path = $request->file('id_photo')->store('identity_photos', 'private');

            // Delete the old one if it exists
            if ($client->id_photo_path) {
                Storage::disk('private')->delete('identity_photos/'.$client->id_photo_path);
            }

            $client->id_photo_path = basename($path);
        }

        $client->save();

        return response()->json($client);
    }

    public function showPhoto(string $filename)
    {
        // Basic security check to prevent directory traversal
        if (str_contains($filename, '..')) {
            abort(404);
        }

        $fullPath = 'identity_photos/'.$filename;

        if (! Storage::disk('private')->exists($fullPath)) {
            abort(404);
        }

        $file = Storage::disk('private')->get($fullPath);
        $mimeType = Storage::disk('private')->mimeType($fullPath);

        return response($file, 200)->header('Content-Type', $mimeType);
    }
}
