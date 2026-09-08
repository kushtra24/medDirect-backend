<?php

namespace App\Http\Requests\Api;

use App\Models\LabResultItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLabResultReportRequest extends FormRequest
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
            'patient_id' => ['nullable', 'integer', 'exists:patients,id'],
            'order_id' => ['nullable', 'integer', 'exists:patient_orders,id'],
            'first_name' => ['required_without:patient_id', 'string', 'max:120'],
            'surname' => ['required_without:patient_id', 'string', 'max:120'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'in:male,female,other,prefer-not-say'],
            'doctor_note' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'finalized'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.test_name' => ['required', 'string', 'max:255'],
            'items.*.value' => ['nullable', 'string', 'max:64'],
            'items.*.status' => ['nullable', 'string', Rule::in([
                LabResultItem::STATUS_NORMAL,
                LabResultItem::STATUS_HIGH,
                LabResultItem::STATUS_LOW,
            ])],
            'items.*.reference_point' => ['nullable', 'string', 'max:128'],
        ];
    }
}
