<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConferenceRoom;
use App\Services\ConferenceRoomService;
use Illuminate\Http\JsonResponse;

class ConferenceRoomStatusController extends Controller
{
    protected $conferenceRoomService;

    public function __construct(ConferenceRoomService $conferenceRoomService)
    {
        $this->conferenceRoomService = $conferenceRoomService;
    }

    /**
     * Mark a conference room as available.
     */
    public function setAvailable(ConferenceRoom $conferenceRoom): JsonResponse
    {
        // Check if the user has the required privilege
        if (! auth()->user()->hasPrivilege('manage-conference-rooms')) {
            return response()->json(['message' => 'You do not have permission to manage conference rooms.'], 403);
        }

        try {
            $updatedConferenceRoom = $this->conferenceRoomService->setAvailable($conferenceRoom);

            return response()->json($updatedConferenceRoom);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }
}
