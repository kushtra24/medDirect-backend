<?php

use App\Authorization\OrganizationAbilities;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClinicImageController;
use App\Http\Controllers\Api\ClinicTeamMemberController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExaminationResultReportController;
use App\Http\Controllers\Api\LabResultReportController;
use App\Http\Controllers\Api\MedController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PatientMedicalRecordsController;
use App\Http\Controllers\Api\PatientUpdateNoticeController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\UserProfileController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/patient-records/lookup', [PatientMedicalRecordsController::class, 'lookup']);

Route::get('/med', [MedController::class, 'index']);
Route::get('/med/{slug}', [MedController::class, 'show']);
Route::get('/services', [ServiceController::class, 'index']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', [AuthController::class, 'user']);
    Route::patch('/user/profile', [UserProfileController::class, 'update']);
    Route::patch('/user/password', [UserProfileController::class, 'changePassword']);
    Route::post('/user/avatar', [UserProfileController::class, 'avatar']);
    Route::delete('/user', [UserProfileController::class, 'destroy']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user/medical-records', [PatientMedicalRecordsController::class, 'index']);
    Route::post('/user/claim-records', [PatientMedicalRecordsController::class, 'claim']);

    Route::get('/admin/ping', fn () => ['ok' => true, 'scope' => 'platform_admin'])
        ->middleware('role:'.User::ROLE_ADMIN);

    Route::middleware(['org.member'])->group(function (): void {
        Route::get('/organizations/{organization}', [OrganizationController::class, 'show']);
        Route::patch('/organizations/{organization}', [OrganizationController::class, 'updateProfile'])
            ->middleware(['org.ability:'.OrganizationAbilities::CAN_EDIT_ORGANIZATION_PROFILE]);
        Route::get('/organizations/{organization}/members', [OrganizationController::class, 'members']);
        Route::post('/organizations/{organization}/members', [OrganizationController::class, 'addMember']);
        Route::delete('/organizations/{organization}/members/{memberUser}', [OrganizationController::class, 'removeMember']);
        Route::patch(
            '/organizations/{organization}/members/{memberUser}/abilities',
            [OrganizationController::class, 'updateMemberAbilities']
        );

        Route::post('/organizations/{organization}/images', [ClinicImageController::class, 'store'])
            ->middleware(['org.ability:'.OrganizationAbilities::CAN_EDIT_ORGANIZATION_PROFILE]);
        Route::delete('/organizations/{organization}/images/{clinicImage}', [ClinicImageController::class, 'destroy'])
            ->middleware(['org.ability:'.OrganizationAbilities::CAN_EDIT_ORGANIZATION_PROFILE]);

        Route::post('/organizations/{organization}/team-members', [ClinicTeamMemberController::class, 'store'])
            ->middleware(['org.ability:'.OrganizationAbilities::CAN_EDIT_ORGANIZATION_PROFILE]);
        Route::delete('/organizations/{organization}/team-members/{teamMember}', [ClinicTeamMemberController::class, 'destroy'])
            ->middleware(['org.ability:'.OrganizationAbilities::CAN_EDIT_ORGANIZATION_PROFILE]);

        Route::get('/organizations/{organization}/dashboard-stats', [DashboardController::class, 'stats'])
            ->middleware(['org.ability:'.OrganizationAbilities::CAN_VIEW_PATIENTS]);

        Route::get('/organizations/{organization}/patients', [PatientController::class, 'index'])
            ->middleware(['org.ability:'.OrganizationAbilities::CAN_VIEW_PATIENTS]);
        Route::post('/organizations/{organization}/patients', [PatientController::class, 'store'])
            ->middleware(['org.ability:'.OrganizationAbilities::CAN_CREATE_PATIENT]);
        Route::post('/organizations/{organization}/patients/import', [PatientController::class, 'import'])
            ->middleware(['org.ability:'.OrganizationAbilities::CAN_CREATE_PATIENT]);
        Route::post('/organizations/{organization}/patients/match', [PatientController::class, 'matchUser'])
            ->middleware(['org.ability:'.OrganizationAbilities::CAN_CREATE_PATIENT]);
        Route::get('/organizations/{organization}/patients/{patient}', [PatientController::class, 'show'])
            ->middleware(['org.ability:'.OrganizationAbilities::CAN_VIEW_PATIENTS]);

        Route::get('/organizations/{organization}/patient-notices', [PatientUpdateNoticeController::class, 'index'])
            ->middleware(['org.ability:'.OrganizationAbilities::CAN_VIEW_PATIENTS]);
        Route::post('/organizations/{organization}/patient-notices/read-all', [PatientUpdateNoticeController::class, 'markAllRead'])
            ->middleware(['org.ability:'.OrganizationAbilities::CAN_VIEW_PATIENTS]);
        Route::post(
            '/organizations/{organization}/patient-notices/{patientUpdateNotice}/read',
            [PatientUpdateNoticeController::class, 'markRead']
        )->middleware(['org.ability:'.OrganizationAbilities::CAN_VIEW_PATIENTS]);

        Route::post('/organizations/{organization}/lab-results', [LabResultReportController::class, 'store'])
            ->middleware(['org.ability:'.OrganizationAbilities::CAN_CREATE_ADD_RESULTS]);

        Route::post('/organizations/{organization}/examination-results', [ExaminationResultReportController::class, 'store'])
            ->middleware(['org.ability:'.OrganizationAbilities::CAN_CREATE_ADD_RESULTS]);
    });
});
