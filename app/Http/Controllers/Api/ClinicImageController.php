<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicImage;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ClinicImageController extends Controller
{
    private const SINGLE_TYPES = [ClinicImage::TYPE_LOGO, ClinicImage::TYPE_HERO];

    private const GALLERY_MAX = 3;

    public function store(Request $request, Organization $organization): JsonResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'max:5120'],
            'type' => ['required', 'string', Rule::in([
                ClinicImage::TYPE_LOGO,
                ClinicImage::TYPE_HERO,
                ClinicImage::TYPE_GALLERY,
            ])],
        ]);

        $type = $validated['type'];

        if (in_array($type, self::SINGLE_TYPES, true)) {
            $existing = $organization->clinicImages()->where('type', $type)->get();
            foreach ($existing as $image) {
                $this->deleteFile($image);
                $image->delete();
            }
            $sortOrder = 0;
        } else {
            $galleryCount = $organization->clinicImages()->where('type', ClinicImage::TYPE_GALLERY)->count();
            if ($type === ClinicImage::TYPE_GALLERY && $galleryCount >= self::GALLERY_MAX) {
                abort(422, 'Maximum of '.self::GALLERY_MAX.' gallery photos reached.');
            }
            $sortOrder = (int) $organization->clinicImages()->where('type', $type)->max('sort_order') + 1;
        }

        $path = $request->file('image')->store('clinics/'.$organization->id.'/'.$type, 'public');

        $image = $organization->clinicImages()->create([
            'url' => Storage::disk('public')->url($path),
            'type' => $type,
            'sort_order' => $sortOrder,
        ]);

        return response()->json([
            'image' => ['id' => $image->id, 'url' => $image->url, 'type' => $image->type],
        ], 201);
    }

    public function destroy(Request $request, Organization $organization, ClinicImage $clinicImage): JsonResponse
    {
        if ($clinicImage->organization_id !== $organization->id) {
            abort(404);
        }

        $this->deleteFile($clinicImage);
        $clinicImage->delete();

        return response()->json(['message' => 'ok']);
    }

    private function deleteFile(ClinicImage $image): void
    {
        $prefix = rtrim(Storage::disk('public')->url(''), '/').'/';
        if (! str_starts_with($image->url, $prefix)) {
            return;
        }

        $path = substr($image->url, strlen($prefix));
        Storage::disk('public')->delete($path);
    }
}
