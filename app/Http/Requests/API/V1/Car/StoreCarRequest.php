<?php

namespace App\Http\Requests\API\V1\Car;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCarRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'brand' => ['required', 'string'],
            'serie' => ['required', 'string'],
            'type' => ['required', 'string'],
            'seats_number' => ['required', 'numeric'],
            'thumbnail' => ['required', 'string'],
        ];
    }
}
