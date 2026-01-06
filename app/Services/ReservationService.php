<?php

namespace App\Services;

use App\Models\Reservation;
use Exception;

class ReservationService
{
    public function checkin(Reservation $reservation): Reservation
    {
        $client = $reservation->client;
        if (! $client->id_type || ! $client->id_number || ! $client->id_photo_path) {
            throw new Exception('Les informations d\'identité du client (type, numéro et photo) sont requises pour le check-in.', 422);
        }

        if ($reservation->status !== 'confirmed') {
            throw new Exception('Seules les réservations confirmées peuvent être check-in.', 400);
        }

        // Update reservation
        $reservation->status = 'checked-in';
        $reservation->save();

        // Update the reservable item's status
        if ($reservation->reservable_type !== 'service_only' && $reservation->reservable) {
            $reservation->reservable->status = 'occupied';
            $reservation->reservable->save();
        }

        // Conditionally load reservable
        if ($reservation->reservable_type !== 'service_only' && $reservation->reservable) {
            return $reservation->load('reservable');
        }

        return $reservation;
    }

    public function checkout(Reservation $reservation, bool $force = false): Reservation
    {
        if ($reservation->remaining_balance > 0 && ! $force) {
            throw new Exception('Le client doit solder la facture avant le check-out. Montant restant : '.$reservation->remaining_balance.' FCFA', 422);
        }

        if ($reservation->status !== 'checked-in') {
            throw new Exception('Seul le check-in des réservations peut être check-out.', 400);
        }

        // Update reservation
        $reservation->status = 'checked-out';
        $reservation->save();

        // Update the reservable item's status to maintenance
        if ($reservation->reservable_type !== 'service_only' && $reservation->reservable) {
            $reservation->reservable->status = 'maintenance';
            $reservation->reservable->save();
        }

        // Conditionally load reservable
        if ($reservation->reservable_type !== 'service_only' && $reservation->reservable) {
            return $reservation->load('reservable');
        }

        return $reservation;
    }

    public function cancel(Reservation $reservation): Reservation
    {
        if (! in_array($reservation->status, ['confirmed', 'pending'])) {
            throw new Exception('Seules les réservations confirmées ou en attente peuvent être annulées.', 400);
        }

        // Update reservation
        $reservation->status = 'canceled';
        $reservation->save();

        // Update the reservable item's status to available
        if ($reservation->reservable_type !== 'service_only' && $reservation->reservable) {
            $reservation->reservable->status = 'available';
            $reservation->reservable->save();
        }

        // Conditionally load reservable
        if ($reservation->reservable_type !== 'service_only' && $reservation->reservable) {
            return $reservation->load('reservable');
        }

        return $reservation;
    }

    public function confirm(Reservation $reservation): Reservation
    {
        if ($reservation->status !== 'pending') {
            throw new Exception('Seules les réservations en attente peuvent être confirmées.', 400);
        }

        // Update reservation
        $reservation->status = 'confirmed';
        $reservation->save(); // Sauvegarder la modification dans la base de données

        // Conditionally load reservable
        if ($reservation->reservable_type !== 'service_only' && $reservation->reservable) {
            return $reservation->load('reservable');
        }

        return $reservation;
    }
}
