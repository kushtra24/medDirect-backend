<?php

namespace App\Http\Requests\Api;

use App\Authorization\OrganizationAbilities;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationMemberAbilitiesRequest extends FormRequest
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
            'abilities' => ['sometimes', 'array'],
            'abilities.*' => ['boolean'],
            'title' => ['sometimes', 'nullable', 'string', 'max:120'],
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
