<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\LabResultItem */
class LabResultItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'test_name' => $this->test_name,
            'value' => $this->value,
            'status' => $this->status,
            'reference_point' => $this->reference_point,
            'sort_order' => $this->sort_order,
        ];
    }
}
