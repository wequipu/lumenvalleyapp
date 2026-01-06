<?php

namespace App\Exports;

use App\Models\Accommodation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AccommodationsExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Accommodation::all();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Number',
            'Type',
            'Nightly Rate',
            'Status',
            'Created At',
            'Updated At',
        ];
    }
}
