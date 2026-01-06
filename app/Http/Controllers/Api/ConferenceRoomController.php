<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConferenceRoomRequest;
use App\Http\Requests\UpdateConferenceRoomRequest;
use App\Models\ConferenceRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConferenceRoomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Check if the user has the required privilege
        if (! auth()->user()->hasPrivilege('manage-conference-rooms')) {
            return response()->json(['message' => 'You do not have permission to manage conference rooms.'], 403);
        }

        $query = ConferenceRoom::query();

        // Global search
        $query->when($request->query('q'), function ($q, $searchTerm) {
            return $q->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('room_number', 'like', "%{$searchTerm}%");
        });

        $query->when($request->query('name'), function ($q, $name) {
            return $q->where('name', 'like', "%{$name}%");
        });

        $query->when($request->query('room_number'), function ($q, $number) {
            return $q->where('room_number', 'like', "%{$number}%");
        });

        $query->when($request->query('status'), function ($q, $status) {
            return $q->where('status', $status);
        });

        if ($request->has('page')) {
            $conferenceRooms = $query->paginate(10);
        } else {
            $conferenceRooms = $query->get();
        }

        return response()->json($conferenceRooms);
    }

    public function all(): JsonResponse
    {
        // Check if the user has the required privilege
        if (! auth()->user()->hasPrivilege('manage-conference-rooms')) {
            return response()->json(['message' => 'You do not have permission to manage conference rooms.'], 403);
        }

        $conferenceRooms = ConferenceRoom::all();

        return response()->json($conferenceRooms);
    }

    public function store(StoreConferenceRoomRequest $request): JsonResponse
    {
        // Check if the user has the required privilege
        if (! auth()->user()->hasPrivilege('manage-conference-rooms')) {
            return response()->json(['message' => 'You do not have permission to manage conference rooms.'], 403);
        }

        $validatedData = $request->validated();
        $conferenceRoom = ConferenceRoom::create($validatedData);

        return response()->json($conferenceRoom, 201);
    }

    public function show(ConferenceRoom $conferenceRoom): JsonResponse
    {
        // Check if the user has the required privilege
        if (! auth()->user()->hasPrivilege('manage-conference-rooms')) {
            return response()->json(['message' => 'You do not have permission to manage conference rooms.'], 403);
        }

        return response()->json($conferenceRoom);
    }

    public function update(UpdateConferenceRoomRequest $request, ConferenceRoom $conferenceRoom): JsonResponse
    {
        // Check if the user has the required privilege
        if (! auth()->user()->hasPrivilege('manage-conference-rooms')) {
            return response()->json(['message' => 'You do not have permission to manage conference rooms.'], 403);
        }

        $validatedData = $request->validated();
        $conferenceRoom->update($validatedData);

        return response()->json($conferenceRoom);
    }

    public function destroy(ConferenceRoom $conferenceRoom): JsonResponse
    {
        // Check if the user has the required privilege
        if (! auth()->user()->hasPrivilege('manage-conference-rooms')) {
            return response()->json(['message' => 'You do not have permission to manage conference rooms.'], 403);
        }

        $conferenceRoom->delete();

        return response()->json(null, 204);
    }
}
