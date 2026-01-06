<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccommodationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by route middleware for now
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'accommodation_number' => 'required|string|max:255|unique:accommodations,accommodation_number',
            'accommodation_type_id' => 'required_without:new_accommodation_type|nullable|integer|exists:accommodation_types,id',
            'new_accommodation_type' => 'nullable|string|max:255|unique:accommodation_types,name',
            'nightly_rate' => 'required|numeric',
            'status' => 'sometimes|string|in:available,unavailable,occupied,maintenance',
            'photo_path' => 'nullable|string',
        ];
    }
}
