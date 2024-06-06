<?php

namespace App\Http\Requests\API\V1\User\Car;

use Illuminate\Foundation\Http\FormRequest;

class AttachCarRequest extends FormRequest
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
            'car_id' => ['required', 'string'],
            'color' => ['required', 'string'],
            'year' => ['required', 'numeric'],
            'thumbnail' => ['required', 'string'],
        ];
    }
}
