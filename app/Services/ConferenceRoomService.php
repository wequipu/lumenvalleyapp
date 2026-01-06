<?php

namespace App\Services;

use App\Models\ConferenceRoom;
use Exception;

class ConferenceRoomService
{
    public function setAvailable(ConferenceRoom $conferenceRoom): ConferenceRoom
    {
        if ($conferenceRoom->status !== 'maintenance') {
            throw new Exception('Seules les salles en maintenance peuvent être rendues disponibles.', 400);
        }

        $conferenceRoom->status = 'available';
        $conferenceRoom->save();

        return $conferenceRoom;
    }
}
