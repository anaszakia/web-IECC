<?php

use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Incident\CommandCenterController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Laravel\Passkeys\Http\Controllers\PasskeyConfirmationController;
use Laravel\Passkeys\Http\Controllers\PasskeyLoginController;
use Laravel\Passkeys\Http\Controllers\PasskeyRegistrationController;

Broadcast::routes(['middleware' => ['web']]);

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::get('/forgot-password', fn() => view('auth.forgot-password'))->name('password.request');
Route::post('/forgot-password', fn() => back()->with('status', 'Link dikirim!'))->middleware('throttle:5,1')->name('password.email');
Route::get('/reset-password/{token?}', fn($token = '') => view('auth.reset-password', compact('token')))->name('password.reset');
Route::post('/reset-password', fn() => redirect()->route('login'))->middleware('throttle:5,1')->name('password.update');
Route::get('/otp-verification', fn() => view('auth.otp-verification'))->name('otp.verification');
Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// Passkeys
Route::get('/passkeys/login/options', [PasskeyLoginController::class, 'index'])
    ->middleware('guest:web')
    ->name('passkey.login-options');
Route::post('/passkeys/login', [PasskeyLoginController::class, 'store'])
    ->middleware('guest:web')
    ->name('passkey.login');

Route::middleware(['auth:web', 'auth.custom'])->group(function () {
    Route::get('/passkeys/confirm/options', [PasskeyConfirmationController::class, 'index'])
        ->name('passkey.confirm-options');
    Route::post('/passkeys/confirm', [PasskeyConfirmationController::class, 'store'])
        ->name('passkey.confirm');
    Route::get('/user/passkeys/options', [PasskeyRegistrationController::class, 'index'])
        ->name('passkey.registration-options');
    Route::post('/user/passkeys', [PasskeyRegistrationController::class, 'store'])
        ->name('passkey.store');
    Route::delete('/user/passkeys/{passkey}', [PasskeyRegistrationController::class, 'destroy'])
        ->name('passkey.destroy');
});

// Protected routes
Route::middleware(['auth.custom', 'auto.logout'])->group(function () {
    Route::get('/', fn() => redirect()->route('dashboard'));
    Route::get('/dashboard', fn() => view('dashboard'))->name('dashboard');

    // Menu management - hanya admin
    Route::middleware(['role:superadmin'])->group(function () {
        Route::resource('/menus', MenuController::class);
        Route::resource('/roles', RoleController::class);
        Route::post('/permissions/sync-routes', [PermissionController::class, 'syncRoutes'])->name('permissions.sync-routes');
        Route::resource('/permissions', PermissionController::class);
    });

    // Command Center IECC Portal
    Route::get('/command-center', [CommandCenterController::class, 'index'])->name('command-center.index');
    Route::get('/command-center/data', [CommandCenterController::class, 'getActiveData'])->name('command-center.data');
    Route::get('/incidents/history', [\App\Http\Controllers\Incident\IncidentHistoryController::class, 'index'])->name('incidents.history');
    Route::get('/command-center/{ulid}', [CommandCenterController::class, 'show'])->name('command-center.show');
    Route::post('/command-center/{ulid}/verify', [CommandCenterController::class, 'verify'])->name('command-center.verify');
    Route::post('/command-center/{ulid}/dispatch', [CommandCenterController::class, 'dispatchUnit'])->name('command-center.dispatch');

    // Hospital Portal
    Route::get('/hospital-portal', [\App\Http\Controllers\Hospital\HospitalPortalController::class, 'index'])->name('hospital.index');
    Route::post('/hospital-portal/received/{ulid}', [\App\Http\Controllers\Hospital\HospitalPortalController::class, 'markAsReceived'])->name('hospital.received');
    Route::post('/hospital-portal/capacity/{ulid}', [\App\Http\Controllers\Hospital\HospitalPortalController::class, 'updateCapacity'])->name('hospital.capacity.update');

    // Executive City Dashboard
    Route::get('/executive-dashboard', [\App\Http\Controllers\Executive\ExecutiveDashboardController::class, 'index'])->name('executive.index');

    // Keep-alive untuk reset session timeout
    Route::post('/keep-alive', function () {
        session(['last_activity' => now()->timestamp]);
        return response()->json(['status' => 'ok']);
    })->middleware(['auth.custom'])->name('keep.alive');

    // Modul Users (Permissions ditangani otomatis oleh Trait HasAutoPermissions di UserController)
    Route::resource('/users', UserController::class);

    // Modul Master Unit & Unit Members
    Route::resource('/units', \App\Http\Controllers\Admin\UnitController::class);
    Route::post('/units/{unit}/members', [\App\Http\Controllers\Admin\UnitController::class, 'addMember'])->name('units.members.store');
    Route::put('/units/{unit}/members/{member}', [\App\Http\Controllers\Admin\UnitController::class, 'updateMember'])->name('units.members.update');
    Route::delete('/units/{unit}/members/{member}', [\App\Http\Controllers\Admin\UnitController::class, 'removeMember'])->name('units.members.destroy');
});

// Audio Siren Streamer (Public, Content-Type: audio/wav & inline)
Route::get('/sounds/emergency-alarm', function () {
    $path = public_path('sounds/emergency_alarm.wav');
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path, [
        'Content-Type'        => 'audio/wav',
        'Content-Disposition' => 'inline; filename="emergency_alarm.wav"',
        'Cache-Control'       => 'public, max-age=86400',
    ]);
})->name('sounds.emergency');

