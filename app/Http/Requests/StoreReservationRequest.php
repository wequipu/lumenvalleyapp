<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'client_id' => 'required|exists:clients,id',
            'reservable_type' => ['required', 'string', Rule::in(['accommodation', 'conference_room', 'service_only'])],
            'total_price' => 'sometimes|numeric',
            'status' => ['sometimes', Rule::in(['pending', 'proforma', 'confirmed', 'checked-in', 'checked-out', 'canceled'])],
            'rate_type' => 'sometimes|string|in:hourly,daily',
            'duration_units' => 'sometimes|integer|min:1',
            'accommodation_discount_percent' => 'sometimes|numeric|min:0|max:100',
            'conference_room_discount_percent' => 'sometimes|numeric|min:0|max:100',
            'services_discount_percent' => 'sometimes|numeric|min:0|max:100',
            'accommodation_tax_rate' => 'sometimes|numeric|min:0',
            'conference_room_tax_rate' => 'sometimes|numeric|min:0',
            'services_tax_rate' => 'sometimes|numeric|min:0',
            'uses_tax_system' => 'sometimes|boolean',
        ];

        // Add conditional validation based on reservation type
        if ($this->input('reservable_type') !== 'service_only') {
            $rules['reservable_id'] = 'required|integer';
            $rules['checkin_date'] = 'required|date';
            $rules['checkout_date'] = 'required|date|after_or_equal:checkin_date';
        } else {
            $rules['reservable_id'] = 'nullable';
            $rules['checkin_date'] = 'required|date';
            $rules['checkout_date'] = 'required|date|after_or_equal:checkin_date';
        }

        return $rules;
    }
}
