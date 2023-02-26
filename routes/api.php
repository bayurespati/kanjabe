<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\RegionalController;
use App\Http\Controllers\WitelController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\NotifController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CronjobController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\MitraController;

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::POST('/login', [AuthController::class, 'login']);
Route::POST('/upload', [UserController::class, 'upload']);

Route::POST('/otp/generate', [OtpController::class, 'generate_otp']);
Route::POST('/otp/verification', [OtpController::class, 'verification']);
Route::POST('/otp/change_password', [OtpController::class, 'change_password']);

// Route::group([], function () {
Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::POST('/logout', [AuthController::class, 'logout']);

    Route::group([
        'prefix' => 'user',
    ], function () {
        Route::GET('', [UserController::class, 'index']);
        Route::POST('', [UserController::class, 'store']);
        Route::POST('photo', [UserController::class, 'updatePhoto']);
        Route::POST('password', [UserController::class, 'changePassword']);
        Route::PATCH('{user}', [UserController::class, 'update']);
        Route::DELETE('{user}', [UserController::class, 'destroy']);
    });

    Route::group([
        'prefix' => 'activity',
    ], function () {
        Route::GET('', [ActivityController::class, 'index']);
        Route::GET('export', [ActivityController::class, 'export']);
        Route::GET('export-detail', [ActivityController::class, 'exportDetail']);
        Route::GET('per-regional', [ActivityController::class, 'getPerRegional']);
        Route::GET('per-mitra', [ActivityController::class, 'getPerMitra']);
        Route::GET('all', [ActivityController::class, 'getAll']);
        Route::GET('progress', [ActivityController::class, 'getProgress']);
        Route::GET('done', [ActivityController::class, 'getDone']);
        Route::GET('done-and-on-progress', [ActivityController::class, 'getDoneAndProgress']);
        Route::GET('on-progress', [ActivityController::class, 'getOnProgress']);
        Route::GET('overview', [ActivityController::class, 'overview']);
        Route::POST('', [ActivityController::class, 'store']);
        Route::POST('progress', [ActivityController::class, 'updateProgress']);
        Route::PATCH('{activity}', [ActivityController::class, 'update']);
        Route::DELETE('{activity}', [ActivityController::class, 'destroy']);
    });

    Route::group([
        'prefix' => 'holiday',
    ], function () {
        Route::GET('', [HolidayController::class, 'index']);
        Route::POST('', [HolidayController::class, 'store']);
        Route::PATCH('{holiday}', [HolidayController::class, 'update']);
        Route::DELETE('{holiday}', [HolidayController::class, 'destroy']);
    });

    Route::group([
        'prefix' => 'regional',
    ], function () {
        Route::GET('', [RegionalController::class, 'index']);
        Route::GET('with-witel', [RegionalController::class, 'getWithWitel']);
        Route::POST('', [RegionalController::class, 'store']);
        Route::PATCH('{regional}', [RegionalController::class, 'update']);
        Route::DELETE('{regional}', [RegionalController::class, 'destroy']);
    });

    Route::group([
        'prefix' => 'mitra',
    ], function () {
        Route::GET('', [MitraController::class, 'index']);
        Route::POST('', [MitraController::class, 'store']);
        Route::PATCH('{mitra}', [MitraController::class, 'update']);
        Route::DELETE('{mitra}', [MitraController::class, 'destroy']);
    });

    Route::group([
        'prefix' => 'witel',
    ], function () {
        Route::GET('', [WitelController::class, 'index']);
        Route::GET('by-regional', [WitelController::class, 'getByRegionalId']);
        Route::POST('', [WitelController::class, 'store']);
        Route::PATCH('{witel}', [WitelController::class, 'update']);
        Route::DELETE('{witel}', [WitelController::class, 'destroy']);
    });

    Route::group([
        'prefix' => 'absensi',
    ], function () {

        Route::group([
            'prefix' => 'user',
        ], function () {
            Route::GET('daily', [AbsensiController::class, 'getUserDaily']);
            Route::GET('weekly', [AbsensiController::class, 'getUserWeekly']);
            Route::GET('report', [AbsensiController::class, 'getUserReport']);
            Route::GET('summary', [AbsensiController::class, 'summary']);
        });

        Route::group([
            'prefix' => 'users',
        ], function () {
            Route::GET('present', [DashboardController::class, 'getPresentAllUser']);
            Route::GET('status', [DashboardController::class, 'getStatusAllUser']);
            Route::GET('daily', [AbsensiController::class, 'getUsersDaily']);
            Route::GET('monthly', [AbsensiController::class, 'getUsersMonthly']);
            Route::GET('employe', [AbsensiController::class, 'getEmploye']);
        });

        Route::GET('export-personal', [AbsensiController::class, 'exportPersonal']);
        Route::GET('export-user-by-regional', [AbsensiController::class, 'exportUserByRegional']);
        Route::POST('check-in', [AbsensiController::class, 'checkIn']);
        Route::POST('check-out/{absensi}', [AbsensiController::class, 'checkOut']);
    });

    Route::group([
        'prefix' => 'notifikasi',
    ], function () {
        Route::POST('notif-to-subordinate', [NotifController::class, 'sendNotifToSubordinate']);
    });

    Route::group([
        'prefix' => 'cronjob',
    ], function () {
        Route::POST('checkout-by-system', [CronjobController::class, 'checkOutUser']);
        Route::POST('checkout-shift', [CronjobController::class, 'checkOutNightShifting']);
        Route::POST('input-not-present', [CronjobController::class, 'inputNotPresent']);
        Route::POST('checkout-overtime', [CronjobController::class, 'checkoutOvertime']);
        Route::POST('test', [CronjobController::class, 'tester']);
    });
});
