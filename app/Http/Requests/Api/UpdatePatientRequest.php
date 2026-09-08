<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Same shape as creating a patient — the edit form always submits the full record.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return StorePatientRequest::baseRules();
    }
}
