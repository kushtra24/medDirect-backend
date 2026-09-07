<?php

namespace App\Http\Requests\Api;

use App\Authorization\OrganizationAbilities;
use Illuminate\Foundation\Http\FormRequest;

class AddOrganizationMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
            'title' => ['nullable', 'string', 'max:120'],
            'is_owner' => ['nullable', 'boolean'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $abilities = $this->input('abilities', []);
            $allowed = OrganizationAbilities::keys();
            foreach (array_keys($abilities) as $key) {
                if (! in_array($key, $allowed, true)) {
                    $validator->errors()->add('abilities', "Unknown ability key: {$key}");
                }
            }
        });
    }
}
