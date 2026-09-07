<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'languages_spoken' => ['nullable', 'array'],
            'languages_spoken.*' => ['string', 'max:64'],
            'diplomas' => ['nullable', 'array'],
            'diplomas.*' => ['string', 'max:255'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['integer', 'exists:services,id'],
            'opening_hours' => ['nullable', 'array', 'size:7'],
            'opening_hours.*.day' => ['required', 'string', 'max:8'],
            'opening_hours.*.days' => ['nullable', 'string', 'max:64'],
            'opening_hours.*.open' => ['nullable', 'string', 'max:8'],
            'opening_hours.*.close' => ['nullable', 'string', 'max:8'],
            'opening_hours.*.closed' => ['boolean'],
            'opening_hours.*.time' => ['nullable', 'string', 'max:64'],
            'opening_hours.*.highlight' => ['boolean'],
        ];
    }
}
