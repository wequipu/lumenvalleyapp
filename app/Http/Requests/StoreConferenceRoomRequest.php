<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConferenceRoomRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'room_number' => 'required|string|unique:conference_rooms|max:255',
            'capacity' => 'required|integer',
            'hourly_rate' => 'nullable|numeric',
            'daily_rate' => 'nullable|numeric',
            'equipment' => 'nullable|string',
            'is_air_conditioned' => 'sometimes|boolean',
            'photo_path' => 'nullable|string',
            'status' => 'sometimes|in:available,occupied,maintenance',
        ];
    }
}
