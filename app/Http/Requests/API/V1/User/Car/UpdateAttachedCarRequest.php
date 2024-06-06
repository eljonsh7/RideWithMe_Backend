<?php

namespace App\Http\Requests\API\V1\User\Car;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttachedCarRequest extends FormRequest
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
            'car_id' => ['nullable', 'string'],
            'color' => ['nullable', 'string'],
            'year' => ['nullable', 'numeric'],
            'thumbnail' => ['nullable', 'string'],
        ];
    }
}
