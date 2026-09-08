<?php

namespace App\Http\Controllers\Api;

use App\Authorization\OrganizationAbilities;
use App\Http\Controllers\Api\Concerns\ChecksPatientOwnership;
use App\Http\Controllers\Controller;
use App\Http\Resources\PatientOrderResource;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PatientOrderController extends Controller
{
    use ChecksPatientOwnership;

    /**
     * Front-desk step: a patient has arrived, log why and route it to the lab (unassigned,
     * anyone with results access can pick it up) or to a specific doctor.
     */
    public function store(Request $request, Organization $organization, Patient $patient): JsonResponse
    {
        $this->assertPatientInOrganization($patient, $organization);

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in([PatientOrder::TYPE_LAB, PatientOrder::TYPE_EXAMINATION])],
            'reason' => ['nullable', 'string', 'max:2000'],
            'assigned_to_user_id' => ['nullable', 'integer'],
        ]);

        if (! empty($validated['assigned_to_user_id'])) {
            $this->assertUserIsOrgMember((int) $validated['assigned_to_user_id'], $organization);
        }

        $order = $patient->orders()->create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $request->user()->id,
            'assigned_to_user_id' => $validated['assigned_to_user_id'] ?? null,
            'type' => $validated['type'],
            'reason' => $validated['reason'] ?? null,
            'status' => PatientOrder::STATUS_PENDING,
        ]);

        $order->load(['patient', 'assignedTo', 'createdBy']);

        return response()->json([
            'order' => new PatientOrderResource($order),
        ], 201);
    }

    /**
     * The org-wide queue: "my orders", the unassigned pool, or everything — what the lab
     * tech or doctor reviews instead of hunting through each patient's record.
     */
    public function index(Request $request, Organization $organization): JsonResponse
    {
        $scope = $request->string('scope', 'all')->toString();
        $statusFilter = $request->string('status', 'open')->toString();

        $query = PatientOrder::query()
            ->where('organization_id', $organization->id)
            ->with(['patient', 'assignedTo', 'createdBy']);

        if ($scope === 'mine') {
            $query->where('assigned_to_user_id', $request->user()->id);
        } elseif ($scope === 'unassigned') {
            $query->whereNull('assigned_to_user_id');
        }

        if ($statusFilter === 'open') {
            $query->whereIn('status', PatientOrder::OPEN_STATUSES);
        } elseif (in_array($statusFilter, [PatientOrder::STATUS_COMPLETED, PatientOrder::STATUS_CANCELLED], true)) {
            $query->where('status', $statusFilter);
        }

        $orders = $query->orderByDesc('created_at')->limit(100)->get();

        return response()->json([
            'orders' => PatientOrderResource::collection($orders),
        ]);
    }

    /**
     * Reassign, self-claim, change status, or cancel. Anyone who can route work
     * (canCreateOrder) or do the work (canCreateAddResults) may adjust an order — not
     * gated behind a single ability since either side of the desk needs this.
     */
    public function update(Request $request, Organization $organization, PatientOrder $order): JsonResponse
    {
        if ($order->organization_id !== $organization->id) {
            abort(404);
        }

        $user = $request->user();
        $canManage = $user->isPlatformAdmin()
            || $user->hasOrganizationAbility($organization, OrganizationAbilities::CAN_CREATE_ORDER)
            || $user->hasOrganizationAbility($organization, OrganizationAbilities::CAN_CREATE_ADD_RESULTS);

        if (! $canManage) {
            abort(403);
        }

        $validated = $request->validate([
            'assigned_to_user_id' => ['sometimes', 'nullable', 'integer'],
            'status' => ['sometimes', 'string', Rule::in([
                PatientOrder::STATUS_PENDING,
                PatientOrder::STATUS_IN_PROGRESS,
                PatientOrder::STATUS_CANCELLED,
            ])],
            'reason' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        if (array_key_exists('assigned_to_user_id', $validated) && $validated['assigned_to_user_id'] !== null) {
            $this->assertUserIsOrgMember((int) $validated['assigned_to_user_id'], $organization);
        }

        $order->update($validated);
        $order->load(['patient', 'assignedTo', 'createdBy']);

        return response()->json([
            'order' => new PatientOrderResource($order),
        ]);
    }

    private function assertUserIsOrgMember(int $userId, Organization $organization): void
    {
        $isMember = $organization->members()->where('user_id', $userId)->exists();

        if (! $isMember) {
            abort(422, 'The assignee must be a member of this organization.');
        }
    }
}
