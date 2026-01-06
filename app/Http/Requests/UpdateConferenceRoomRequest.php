<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConferenceRoomRequest extends FormRequest
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
        $conferenceRoomId = $this->route('conference_room')->id;

        return [
            'name' => 'sometimes|required|string|max:255',
            'room_number' => 'sometimes|required|string|max:255|unique:conference_rooms,room_number,'.$conferenceRoomId,
            'capacity' => 'sometimes|required|integer',
            'hourly_rate' => 'sometimes|nullable|numeric',
            'daily_rate' => 'sometimes|nullable|numeric',
            'equipment' => 'nullable|string',
            'is_air_conditioned' => 'sometimes|boolean',
            'photo_path' => 'nullable|string',
            'status' => 'sometimes|in:available,occupied,maintenance',
        ];
    }
}
