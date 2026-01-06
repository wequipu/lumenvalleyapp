<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
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
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|unique:clients|max:255',
            'email' => 'nullable|email|unique:clients,email',
            'address' => 'nullable|string',
            'date_enregistrement' => 'nullable|date',
            'id_type' => 'nullable|in:cni,passport,driving_license,resident_permit',
            'id_number' => 'nullable|string|max:255',
            'id_photo_path' => 'nullable|string',
        ];
    }
}
