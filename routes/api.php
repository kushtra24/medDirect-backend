<?php

use App\Authorization\OrganizationAbilities;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MedController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\UserProfileController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/med', [MedController::class, 'index']);
Route::get('/med/{slug}', [MedController::class, 'show']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', [AuthController::class, 'user']);
    Route::patch('/user/profile', [UserProfileController::class, 'update']);
    Route::post('/user/avatar', [UserProfileController::class, 'avatar']);
    Route::delete('/user', [UserProfileController::class, 'destroy']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/admin/ping', fn () => ['ok' => true, 'scope' => 'platform_admin'])
        ->middleware('role:'.User::ROLE_ADMIN);

    Route::middleware(['org.member'])->group(function (): void {
        Route::get('/organizations/{organization}', [OrganizationController::class, 'show']);
        Route::patch(
            '/organizations/{organization}/members/{memberUser}/abilities',
            [OrganizationController::class, 'updateMemberAbilities']
        );
    });

    Route::post(
        '/organizations/{organization}/patients',
        [OrganizationController::class, 'storePatientPlaceholder']
    )->middleware(['org.ability:'.OrganizationAbilities::CAN_CREATE_PATIENT]);
});
