<?php

namespace App\Http\Requests\Api;

use App\Models\ExaminationResultItem;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExaminationResultReportRequest extends FormRequest
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
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'doctor_note' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'finalized'])],
            'items' => ['present', 'array'],
            'items.*.finding_name' => ['required', 'string', 'max:255'],
            'items.*.value' => ['nullable', 'string', 'max:64'],
            'items.*.status' => ['nullable', 'string', Rule::in([
                ExaminationResultItem::STATUS_NORMAL,
                ExaminationResultItem::STATUS_HIGH,
                ExaminationResultItem::STATUS_LOW,
            ])],
            'items.*.reference_point' => ['nullable', 'string', 'max:128'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $hasNote = trim((string) $this->input('doctor_note')) !== '';
            $hasItems = count((array) $this->input('items', [])) > 0;

            if (! $hasNote && ! $hasItems) {
                $validator->errors()->add('items', 'Add at least one finding or a doctor note before saving.');
            }
        });
    }
}