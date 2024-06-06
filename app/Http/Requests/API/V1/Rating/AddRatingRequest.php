<?php

namespace App\Http\Requests\API\V1\Rating;

use Illuminate\Foundation\Http\FormRequest;

class AddRatingRequest extends FormRequest
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
            'stars_number' => ['required', 'integer'],
            'description' => ['string', 'nullable']
        ];
    }
}
