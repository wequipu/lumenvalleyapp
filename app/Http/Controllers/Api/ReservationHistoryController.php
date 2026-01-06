<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;

class ReservationHistoryController extends Controller
{
    public function index(Reservation $reservation): JsonResponse
    {
        $audits = $reservation->audits()->with('user')->get();

        return response()->json($audits);
    }
}
