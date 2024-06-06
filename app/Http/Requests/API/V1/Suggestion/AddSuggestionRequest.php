<?php

namespace App\Http\Requests\API\V1\Suggestion;

use Illuminate\Foundation\Http\FormRequest;

class AddSuggestionRequest extends FormRequest
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
            'suggestion_type' => ['required', 'string'],
            'suggestion_content' => ['required', 'string']
        ];
    }
}
