<?php

namespace App\Exports;

use App\Models\Reservation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ReservationsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Reservation::with(['client']);

        if (! empty($this->filters['start_date'])) {
            $query->whereDate('checkin_date', '>=', $this->filters['start_date']);
        }

        if (! empty($this->filters['end_date'])) {
            $query->whereDate('checkout_date', '<=', $this->filters['end_date']);
        }

        $reservations = $query->get();

        // Charger la relation réservable conditionnellement pour les types appropriés
        $reservations->each(function ($reservation) {
            if ($reservation->reservable_type !== 'service_only' && ! empty($reservation->reservable_id)) {
                $reservation->load('reservable');
            }
        });

        return $reservations;
    }

    public function headings(): array
    {
        return [
            'Client',
            'Reservable',
            'Check-in Date',
            'Check-out Date',
            'Total Price',
            'Status',
        ];
    }

    public function map($reservation): array
    {
        $reservableName = '';
        if ($reservation->reservable_type !== 'service_only' && ! empty($reservation->reservable_id) && $reservation->reservable) {
            $reservableName = $reservation->reservable->name;
        } else {
            $reservableName = 'Service seulement';
        }

        return [
            $reservation->client->first_name.' '.$reservation->client->last_name,
            $reservableName,
            $reservation->checkin_date,
            $reservation->checkout_date,
            $reservation->total_price,
            $reservation->status,
        ];
    }
}
