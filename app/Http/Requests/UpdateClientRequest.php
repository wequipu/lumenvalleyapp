<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
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
        $clientId = $this->route('client')->id;

        return [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:255|unique:clients,phone,'.$clientId,
            'email' => 'sometimes|nullable|email|unique:clients,email,'.$clientId,
            'address' => 'nullable|string',
            'date_enregistrement' => 'sometimes|nullable|date',
            'id_type' => 'nullable|in:cni,passport,driving_license,resident_permit',
            'id_number' => 'nullable|string|max:255',
            'id_photo_path' => 'nullable|string',
        ];
    }
}
