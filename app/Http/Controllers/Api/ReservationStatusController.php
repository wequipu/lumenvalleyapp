<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;

class ReservationStatusController extends Controller
{
    protected $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    /**
     * Mark a reservation as checked-in.
     */
    public function checkin(Reservation $reservation): JsonResponse
    {
        try {
            $updatedReservation = $this->reservationService->checkin($reservation);

            return response()->json($updatedReservation);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    /**
     * Mark a reservation as checked-out.
     */
    public function checkout(Reservation $reservation): JsonResponse
    {
        $force = request()->boolean('force', false);

        try {
            $updatedReservation = $this->reservationService->checkout($reservation, $force);

            return response()->json($updatedReservation);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    /**
     * Confirm a reservation.
     */
    public function confirm(Reservation $reservation): JsonResponse
    {
        try {
            $updatedReservation = $this->reservationService->confirm($reservation);

            return response()->json($updatedReservation);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    /**
     * Cancel a reservation.
     */
    public function cancel(Reservation $reservation): JsonResponse
    {
        try {
            $updatedReservation = $this->reservationService->cancel($reservation);

            return response()->json($updatedReservation);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }
}
