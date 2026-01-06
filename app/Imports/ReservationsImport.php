<?php

namespace App\Imports;

use App\Models\Reservation;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ReservationsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Reservation([
            'client_id' => $row['client_id'],
            'reservable_id' => $row['reservable_id'],
            'reservable_type' => $row['reservable_type'], // e.g., 'App\\Models\\Accommodation'
            'checkin_date' => $row['checkin_date'],
            'checkout_date' => $row['checkout_date'],
            'total_price' => $row['total_price'],
            'status' => $row['status'],
        ]);
    }
}
