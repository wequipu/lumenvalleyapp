<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClientsExport implements FromCollection, WithHeadings
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Client::query();

        $query->when($this->filters['first_name'] ?? null, function ($q, $name) {
            return $q->where('first_name', 'like', "%{$name}%");
        });

        $query->when($this->filters['last_name'] ?? null, function ($q, $name) {
            return $q->where('last_name', 'like', "%{$name}%");
        });

        $query->when($this->filters['phone'] ?? null, function ($q, $phone) {
            return $q->where('phone', 'like', "%{$phone}%");
        });

        $query->when($this->filters['start_date'] ?? null, function ($q, $start_date) {
            return $q->whereDate('date_enregistrement', '>=', $start_date);
        });

        $query->when($this->filters['end_date'] ?? null, function ($q, $end_date) {
            return $q->whereDate('date_enregistrement', '<=', $end_date);
        });

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'First Name',
            'Last Name',
            'Email',
            'Phone',
            'Address',
            'Created At',
            'Updated At',
        ];
    }
}
