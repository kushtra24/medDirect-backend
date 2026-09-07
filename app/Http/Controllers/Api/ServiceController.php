<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
    public function index(): JsonResponse
    {
        $services = Service::query()->orderBy('name')->get(['id', 'slug', 'name', 'icon', 'icon_color']);

        return response()->json(['services' => $services]);
    }
}
