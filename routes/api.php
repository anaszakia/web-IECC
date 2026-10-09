<?php

use App\Http\Controllers\Api\V1\FieldOfficerController;
use App\Http\Controllers\Api\V1\IncidentReportController;
use App\Http\Controllers\Api\V1\MobileAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // 1. Mobile Authentication
    Route::post('/auth/login', [MobileAuthController::class, 'login'])->name('api.v1.auth.login');

    // 2. Citizen Incident Reporting
    Route::get('/incidents', [IncidentReportController::class, 'getMyIncidents'])->name('api.v1.incidents.index');
    Route::post('/incidents', [IncidentReportController::class, 'store'])->name('api.v1.incidents.store');
    Route::get('/incidents/{ulid}', [IncidentReportController::class, 'show'])->name('api.v1.incidents.show');

    // 3. Field Response App (Petugas Lapangan)
    Route::prefix('field')->group(function () {
        Route::get('/facilities', [FieldOfficerController::class, 'getFacilities'])->name('api.v1.field.facilities');
        Route::get('/tasks', [FieldOfficerController::class, 'getMyTasks'])->name('api.v1.field.tasks');
        Route::post('/assignments/{ulid}/accept', [FieldOfficerController::class, 'acceptTask'])->name('api.v1.field.assignments.accept');
        Route::post('/assignments/{ulid}/reject', [FieldOfficerController::class, 'rejectTask'])->name('api.v1.field.assignments.reject');
        Route::post('/assignments/{ulid}/status', [FieldOfficerController::class, 'updateStatus'])->name('api.v1.field.assignments.status');
        Route::post('/assignments/{ulid}/patient-handover', [FieldOfficerController::class, 'submitPatientHandover'])->name('api.v1.field.assignments.patient-handover');
        Route::post('/units/{ulid}/location', [FieldOfficerController::class, 'updateLocation'])->name('api.v1.field.units.location');
        Route::post('/units/{ulid}/operational-status', [FieldOfficerController::class, 'updateUnitOperationalStatus'])->name('api.v1.field.units.operational-status');
    });
});
