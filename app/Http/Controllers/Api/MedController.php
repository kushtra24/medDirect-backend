<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicImage;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $institutes = Organization::query()
            ->medicalInstitutes()
            ->withPublicProfile()
            ->with([
                'clinicImages' => fn ($q) => $q->where('type', ClinicImage::TYPE_CARD),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Organization $org) => $this->listItem($org));

        return response()->json(['institutes' => $institutes]);
    }

    public function show(string $slug): JsonResponse
    {
        $organization = Organization::query()
            ->where('slug', $slug)
            ->firstOrFail();

        if (! $organization->isMedicalInstitute() || ! $organization->hasPublicProfile()) {
            abort(404);
        }

        $organization->load([
            'clinicAddresses',
            'clinicPhones',
            'clinicImages',
            'services',
        ]);

        return response()->json([
            'institute' => $this->detail($organization),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function listItem(Organization $org): array
    {
        $cardImage = $org->clinicImages->first();

        return [
            'id' => $org->id,
            'slug' => $org->slug,
            'name' => $org->name,
            'type' => $org->type,
            'rating' => $org->rating,
            'distance' => $org->distance_km,
            'neighborhood' => $org->neighborhood,
            'isOpen' => $org->isOpenNow(),
            'openTime' => $org->nextOpenTimeLabel(),
            'image' => $cardImage?->url,
            'allowsAppointments' => $org->allows_appointments,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(Organization $org): array
    {
        $imagesByType = $org->clinicImages->groupBy('type');

        $primaryAddress = $org->clinicAddresses->firstWhere('is_primary', true)
            ?? $org->clinicAddresses->first();

        return [
            'id' => $org->id,
            'slug' => $org->slug,
            'name' => $org->name,
            'type' => $org->type,
            'certified' => $org->certified,
            'description' => $org->description,
            'allowsAppointments' => $org->allows_appointments,
            'healthInsurances' => $org->health_insurances ?? [],
            'languagesSpoken' => $org->languages_spoken ?? [],
            'specialties' => $org->specialties ?? [],
            'diplomas' => $org->diplomas ?? [],
            'addresses' => $org->clinicAddresses->map(fn ($a) => [
                'id' => $a->id,
                'line' => $a->line,
                'description' => $a->description,
                'isPrimary' => $a->is_primary,
            ])->values()->all(),
            'phones' => $org->clinicPhones->map(fn ($p) => [
                'id' => $p->id,
                'number' => $p->number,
                'label' => $p->label,
            ])->values()->all(),
            'address' => $primaryAddress?->line,
            'phone' => $org->clinicPhones->first()?->number,
            'heroImage' => $imagesByType->get(ClinicImage::TYPE_HERO)?->first()?->url,
            'logo' => $imagesByType->get(ClinicImage::TYPE_LOGO)?->first()?->url,
            'mapImage' => $imagesByType->get(ClinicImage::TYPE_MAP)?->first()?->url,
            'services' => $org->services->map(fn ($s) => [
                'icon' => $s->icon,
                'label' => $s->name,
                'iconColor' => $s->icon_color,
            ])->values()->all(),
            'hours' => $org->opening_hours ?? [],
        ];
    }
}
