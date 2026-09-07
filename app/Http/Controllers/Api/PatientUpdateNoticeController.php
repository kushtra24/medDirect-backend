<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PatientUpdateNoticeResource;
use App\Models\Organization;
use App\Models\PatientUpdateNotice;
use Illuminate\Http\JsonResponse;

class PatientUpdateNoticeController extends Controller
{
    public function index(Organization $organization): JsonResponse
    {
        $notices = PatientUpdateNotice::query()
            ->where('organization_id', $organization->id)
            ->with('patient')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'notices' => PatientUpdateNoticeResource::collection($notices),
            'unread_count' => $notices->whereNull('read_at')->count(),
        ]);
    }

    public function markRead(Organization $organization, PatientUpdateNotice $patientUpdateNotice): JsonResponse
    {
        if ($patientUpdateNotice->organization_id !== $organization->id) {
            abort(404);
        }

        if ($patientUpdateNotice->read_at === null) {
            $patientUpdateNotice->update(['read_at' => now()]);
        }

        return response()->json([
            'notice' => new PatientUpdateNoticeResource($patientUpdateNotice),
        ]);
    }

    public function markAllRead(Organization $organization): JsonResponse
    {
        PatientUpdateNotice::query()
            ->where('organization_id', $organization->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'ok']);
    }
}
