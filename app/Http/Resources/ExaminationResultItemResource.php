<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ExaminationResultItem */
class ExaminationResultItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'finding_name' => $this->finding_name,
            'value' => $this->value,
            'status' => $this->status,
            'reference_point' => $this->reference_point,
            'sort_order' => $this->sort_order,
        ];
    }
}