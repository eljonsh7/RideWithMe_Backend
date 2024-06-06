<?php

namespace App\Http\Requests\API\V1\Route;

use Illuminate\Foundation\Http\FormRequest;

class AddRouteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->role === 'driver';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'driver_id' => ['required', 'exists:users,id'],
            'city_from_id' => ['required', 'exists:cities,id'],
            'city_to_id' => ['required', 'exists:cities,id'],
            'location_id' => ['required', 'exists:locations,id'],
            'datetime' => ['required', 'date_format:Y-m-d H:i:s'],
            'passengers_number' => ['required', 'integer', 'min:1'],
            'price' => ['required']
        ];
    }
}
