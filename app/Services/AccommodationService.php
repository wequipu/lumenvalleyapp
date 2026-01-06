<?php

namespace App\Services;

use App\Models\Accommodation;

class AccommodationService
{
    public function setAvailable(Accommodation $accommodation): Accommodation
    {
        $accommodation->status = 'available';
        $accommodation->save();

        return $accommodation;
    }
}
