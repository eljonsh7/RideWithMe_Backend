<?php

namespace App\Http\Requests\API\V1\Car;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->is_admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'brand' => ['nullable', 'string'],
            'serie' => ['nullable', 'string'],
            'type' => ['nullable', 'string'],
            'seats_number' => ['nullable', 'numeric'],
            'thumbnail' => ['nullable', 'string'],
        ];
    }
}
