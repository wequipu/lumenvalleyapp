<?php

namespace App\Imports;

use App\Models\Accommodation;
use App\Models\AccommodationType;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class AccommodationsImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $accommodationType = AccommodationType::firstOrCreate(['name' => $row['type']]);

        return new Accommodation([
            'name' => $row['name'],
            'accommodation_number' => $row['accommodation_number'],
            'type' => $accommodationType->name,
            'accommodation_type_id' => $accommodationType->id,
            'nightly_rate' => $row['nightly_rate'],
            'status' => $row['status'] ?? 'available',
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'accommodation_number' => 'required|string|unique:accommodations,accommodation_number',
            'type' => 'required|string',
            'nightly_rate' => 'required|numeric',
        ];
    }
}
