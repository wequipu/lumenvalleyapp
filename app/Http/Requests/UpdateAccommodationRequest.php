<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccommodationRequest extends FormRequest
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
        return [
            'name' => 'sometimes|string|max:255',
            'accommodation_number' => 'sometimes|string|max:255|unique:accommodations,accommodation_number,'.$this->route('accommodation')->id,
            'accommodation_type_id' => 'nullable|integer|exists:accommodation_types,id',
            'new_accommodation_type' => 'nullable|string|max:255|unique:accommodation_types,name',
            'nightly_rate' => 'sometimes|numeric',
            'status' => 'sometimes|string|in:available,unavailable,occupied,maintenance',
            'photo_path' => 'nullable|string',
        ];
    }
}
