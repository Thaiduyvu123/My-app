<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class VerifyOtpRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower(trim($this->input('email')))]);
        }
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
            'display_name' => ['sometimes', 'string', 'min:2', 'max:40'],
            'birth_date' => ['sometimes', 'date_format:Y-m-d'],
            'consents' => ['sometimes', 'array'],
            'consents.terms' => ['sometimes', 'boolean'],
            'consents.privacy' => ['sometimes', 'boolean'],
        ];
    }
}
