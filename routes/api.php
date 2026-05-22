<?php

use App\Authorization\OrganizationAbilities;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LabResultReportController;
use App\Http\Controllers\Api\MedController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PatientMedicalRecordsController;
use App\Http\Controllers\Api\UserProfileController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/patient-records/lookup', [PatientMedicalRecordsController::class, 'lookup']);

Route::get('/med', [MedController::class, 'index']);
Route::get('/med/{slug}', [MedController::class, 'show']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', [AuthController::class, 'user']);
    Route::patch('/user/profile', [UserProfileController::class, 'update']);
    Route::post('/user/avatar', [UserProfileController::class, 'avatar']);
    Route::delete('/user', [UserProfileController::class, 'destroy']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user/medical-records', [PatientMedicalRecordsController::class, 'index']);
    Route::post('/user/claim-records', [PatientMedicalRecordsController::class, 'claim']);

    Route::get('/admin/ping', fn () => ['ok' => true, 'scope' => 'platform_admin'])
        ->middleware('role:'.User::ROLE_ADMIN);

    Route::middleware(['org.member'])->group(function (): void {
        Route::get('/organizations/{organization}', [OrganizationController::class, 'show']);
        Route::patch(
            '/organizations/{organization}/members/{memberUser}/abilities',
            [OrganizationController::class, 'updateMemberAbilities']
        );

        Route::get('/organizations/{organization}/patients', [PatientController::class, 'index'])
            ->middleware(['org.ability:'.OrganizationAbilities::CAN_VIEW_PATIENTS]);
        Route::post('/organizations/{organization}/patients', [PatientController::class, 'store'])
            ->middleware(['org.ability:'.OrganizationAbilities::CAN_CREATE_PATIENT]);
        Route::get('/organizations/{organization}/patients/{patient}', [PatientController::class, 'show'])
            ->middleware(['org.ability:'.OrganizationAbilities::CAN_VIEW_PATIENTS]);

        Route::post('/organizations/{organization}/lab-results', [LabResultReportController::class, 'store'])
            ->middleware(['org.ability:'.OrganizationAbilities::CAN_CREATE_ADD_RESULTS]);
    });
});
