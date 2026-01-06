<?php

use App\Http\Controllers\Api\AccommodationController;
use App\Http\Controllers\Api\AccommodationStatusController;
use App\Http\Controllers\Api\AccommodationTypeController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ClientIdentityController;
use App\Http\Controllers\Api\ConferenceRoomController;
use App\Http\Controllers\Api\ConferenceRoomStatusController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FileUploadController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\ReservationStatusController;
use App\Http\Controllers\Api\TimeLimitedPrivilegeController;
use App\Http\Controllers\PrivilegeController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

// Route publique pour la connexion
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::get('clients/identity-photo/{filename}', [ClientIdentityController::class, 'showPhoto'])->where('filename', '[a-zA-Z0-9._-]+');
Route::get('reservations/{reservation}/receipt', [ReservationController::class, 'receipt']);
Route::get('reservations/{reservation}/download-pdf', [ReservationController::class, 'downloadReceiptPdf']);

// Routes protégées nécessitant une authentification
Route::middleware('auth:sanctum')->group(function () {
    // Routes pour l'utilisateur authentifié
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/help', [\App\Http\Controllers\Api\HelpController::class, 'index']);

    // Profile
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);

    // Dashboard
    Route::get('/dashboard/occupancy-by-month', [DashboardController::class, 'getOccupancyByMonth']);
    Route::get('/dashboard/top-revenue-generators', [DashboardController::class, 'getTopRevenueGenerators']);
    Route::get('/dashboard/occupancy-variation', [DashboardController::class, 'getOccupancyVariation']);
    Route::get('/dashboard/most-requested-services', [DashboardController::class, 'getMostRequestedServices']);
    Route::get('/dashboard/canceled-reservations-trend', [DashboardController::class, 'getCanceledReservationsTrend']);
    Route::get('/dashboard/stats', [DashboardController::class, 'getDashboardStats']);
    Route::get('/dashboard/statistics', [DashboardController::class, 'getStatistics']);
    Route::get('/dashboard/service-revenue-data', [DashboardController::class, 'getServiceRevenueData']);

    // File Upload
    Route::post('/upload', [FileUploadController::class, 'store']);

    // Routes for accommodations and conference rooms - controlled by privilege in controllers
    Route::get('accommodations/export', [AccommodationController::class, 'export']);
    Route::post('accommodations/import', [AccommodationController::class, 'import']);
    Route::get('accommodations/all', [AccommodationController::class, 'all']);
    Route::apiResource('accommodations', AccommodationController::class);
    Route::apiResource('accommodation-types', AccommodationTypeController::class)->only(['index', 'store']);
    Route::post('/accommodations/{accommodation}/set-available', [AccommodationStatusController::class, 'setAvailable']);
    Route::get('conference-rooms/all', [ConferenceRoomController::class, 'all']);
    Route::apiResource('conference-rooms', ConferenceRoomController::class);
    Route::post('/conference-rooms/{conference_room}/set-available', [ConferenceRoomStatusController::class, 'setAvailable']);

    // Services - accessible to all authenticated users
    Route::apiResource('services', \App\Http\Controllers\Api\ServiceController::class);

    // Admin-only routes
    Route::middleware(['role:Admin'])->group(function () {
        Route::apiResource('privileges', PrivilegeController::class);
        Route::apiResource('roles', RoleController::class);
        Route::post('roles/{role}/privileges', [RoleController::class, 'assignPrivileges']);
    });

    // User management is restricted to Super Admin directly in the controller
    Route::apiResource('users', \App\Http\Controllers\Api\UserController::class);

    Route::middleware(['role:Admin,Receptionist,Salesperson'])->group(function () {
        Route::get('clients/export', [ClientController::class, 'export']);
        Route::get('clients/search', [ClientController::class, 'search']);
        Route::get('clients/all', [ClientController::class, 'all']);
        Route::apiResource('clients', ClientController::class);
        Route::post('clients/{client}/identity', [ClientIdentityController::class, 'store']);

    });

    Route::middleware(['role:Admin,Receptionist'])->group(function () {
        Route::get('reservations/export', [ReservationController::class, 'export']);
        Route::get('reservations/revenue', [ReservationController::class, 'getRevenue']);
        Route::post('reservations/import', [ReservationController::class, 'import']);
        Route::apiResource('reservations', ReservationController::class);
        Route::post('/reservations/{reservation}/checkin', [ReservationStatusController::class, 'checkin']);
        Route::post('/reservations/{reservation}/checkout', [ReservationStatusController::class, 'checkout']);
        Route::post('/reservations/{reservation}/cancel', [ReservationStatusController::class, 'cancel']);
        Route::post('/reservations/{reservation}/confirm', [ReservationStatusController::class, 'confirm']);
        Route::get('reservations/{reservation}/history', [\App\Http\Controllers\Api\ReservationHistoryController::class, 'index']);
        Route::apiResource('payments', \App\Http\Controllers\Api\PaymentController::class)->only(['store', 'destroy']);

    });

    // Time-limited privileges routes
    Route::prefix('time-limited-privileges')->group(function () {
        Route::get('/my-active', [TimeLimitedPrivilegeController::class, 'getActiveForUser']);
        Route::post('/grant', [TimeLimitedPrivilegeController::class, 'grant']);
        Route::delete('/{id}', [TimeLimitedPrivilegeController::class, 'revoke']);
        Route::get('/', [TimeLimitedPrivilegeController::class, 'getAll']);
    });
});
