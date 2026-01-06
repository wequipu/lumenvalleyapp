<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Accommodation;
use App\Services\AccommodationService;
use Illuminate\Http\JsonResponse;

class AccommodationStatusController extends Controller
{
    protected $accommodationService;

    public function __construct(AccommodationService $accommodationService)
    {
        $this->accommodationService = $accommodationService;
    }

    /**
     * Mark an accommodation as available.
     */
    public function setAvailable(Accommodation $accommodation): JsonResponse
    {
        // Check if the user has the required privilege
        if (! auth()->user()->hasPrivilege('manage-accommodations')) {
            return response()->json(['message' => 'You do not have permission to manage accommodations.'], 403);
        }

        try {
            $updatedAccommodation = $this->accommodationService->setAvailable($accommodation);

            return response()->json($updatedAccommodation);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }
}
