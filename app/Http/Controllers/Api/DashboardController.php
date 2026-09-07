<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExaminationResultReport;
use App\Models\LabResultReport;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(Organization $organization): JsonResponse
    {
        $examinationsToday = ExaminationResultReport::query()
            ->where('organization_id', $organization->id)
            ->where('status', ExaminationResultReport::STATUS_FINALIZED)
            ->whereDate('reported_at', today())
            ->count();

        $newPatientsThisMonth = Patient::query()
            ->where('organization_id', $organization->id)
            ->whereBetween('created_at', [now()->startOfMonth(), now()])
            ->count();

        return response()->json([
            'stats' => [
                'examinations_today' => $examinationsToday,
                'new_patients_this_month' => $newPatientsThisMonth,
            ],
            'recent_activity' => $this->recentActivity($organization),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentActivity(Organization $organization): array
    {
        $examinations = ExaminationResultReport::query()
            ->where('organization_id', $organization->id)
            ->with(['patient', 'createdBy'])
            ->orderByDesc('updated_at')
            ->limit(3)
            ->get()
            ->map(fn (ExaminationResultReport $r) => ['type' => 'examination', 'record' => $r]);

        $labResults = LabResultReport::query()
            ->where('organization_id', $organization->id)
            ->with(['patient', 'createdBy'])
            ->orderByDesc('updated_at')
            ->limit(3)
            ->get()
            ->map(fn (LabResultReport $r) => ['type' => 'lab', 'record' => $r]);

        $recent = $examinations->concat($labResults)
            ->sortByDesc(fn (array $entry) => $entry['record']->updated_at)
            ->take(3)
            ->values();

        $staffUserIds = $recent->pluck('record.created_by_user_id')->filter()->unique()->values();
        $titlesByUserId = OrganizationMember::query()
            ->where('organization_id', $organization->id)
            ->whereIn('user_id', $staffUserIds)
            ->pluck('title', 'user_id');

        return $recent->map(function (array $entry) use ($titlesByUserId) {
            $record = $entry['record'];

            return [
                'type' => $entry['type'],
                'patient_name' => $record->patient?->fullName(),
                'staff_name' => $record->createdBy?->name,
                'staff_title' => $titlesByUserId->get($record->created_by_user_id),
                'status' => $record->status,
                'occurred_at' => $record->updated_at?->toIso8601String(),
            ];
        })->values()->all();
    }
}
