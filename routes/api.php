<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminBkpsdm\DashboardController;
use App\Http\Controllers\Api\AdminBkpsdm\PenggunaController;
use App\Http\Controllers\Api\AdminBkpsdm\KomunitasController;
use App\Http\Controllers\Api\AdminBkpsdm\ValidasiPembelajaranController;
use App\Http\Controllers\Api\AdminBkpsdm\VerifikasiJpController;
use App\Http\Controllers\Api\AdminBkpsdm\LaporanController;
use App\Http\Controllers\Api\AdminBkpsdm\PusatBantuanController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/change-password/request-otp', [AuthController::class, 'changePasswordRequestOtp']);
    Route::post('/change-password/verify', [AuthController::class, 'changePasswordVerify']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Admin BKPSDM Routes
Route::middleware(['auth:sanctum', 'role:admin_bkpsdm'])->prefix('admin-bkpsdm')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    
    // Pengguna
    Route::post('/pengguna/{id}/reset-password', [PenggunaController::class, 'resetPassword']);
    Route::apiResource('/pengguna', PenggunaController::class);
    
    // Komunitas
    Route::apiResource('/komunitas', KomunitasController::class);

    // Approval Konten
    Route::get('/approval', [ValidasiPembelajaranController::class, 'index']);
    Route::post('/approval/{pembelajaran_id}', [ValidasiPembelajaranController::class, 'store']);

    // Verifikasi JP
    Route::get('/verifikasi-jp', [VerifikasiJpController::class, 'index']);
    Route::put('/verifikasi-jp/{pembelajaran_id}', [VerifikasiJpController::class, 'update']);

    // Laporan
    Route::get('/laporan/peserta', [LaporanController::class, 'peserta']);
    Route::get('/laporan/peserta/export', [LaporanController::class, 'exportPeserta']);

    // Pusat Bantuan
    Route::get('/tiket', [PusatBantuanController::class, 'tiket']);
    Route::put('/tiket/{id}/tanggapi', [PusatBantuanController::class, 'tanggapiTiket']);
    Route::get('/faq', [PusatBantuanController::class, 'faq']);
    Route::post('/faq', [PusatBantuanController::class, 'storeFaq']);
    Route::put('/faq/{id}', [PusatBantuanController::class, 'updateFaq']);
    Route::delete('/faq/{id}', [PusatBantuanController::class, 'destroyFaq']);
});

// Admin Komunitas Routes
Route::middleware(['auth:sanctum', 'role:admin_komunitas'])->prefix('admin-komunitas')->group(function () {
    // Pembelajaran
    Route::apiResource('/pembelajaran', \App\Http\Controllers\Api\AdminKomunitas\PembelajaranController::class);
    Route::post('/pembelajaran/{id}/ajukan-approval', [\App\Http\Controllers\Api\AdminKomunitas\PembelajaranController::class, 'ajukanApproval']);

    // Modul & Materi
    Route::apiResource('/pembelajaran.modul', \App\Http\Controllers\Api\AdminKomunitas\ModulController::class)->shallow();
    Route::apiResource('/modul.materi', \App\Http\Controllers\Api\AdminKomunitas\MateriController::class)->shallow();

    // Evaluasi
    Route::apiResource('/modul.kuis', \App\Http\Controllers\Api\AdminKomunitas\KuisController::class)->shallow();
    Route::apiResource('/pembelajaran.post-test', \App\Http\Controllers\Api\AdminKomunitas\PostTestController::class)->shallow();

    // JP & Monitoring
    Route::post('/pembelajaran/{id}/jp', [\App\Http\Controllers\Api\AdminKomunitas\PembelajaranJpController::class, 'store']);
    Route::get('/pembelajaran/{id}/peserta', [\App\Http\Controllers\Api\AdminKomunitas\MonitoringController::class, 'index']);
});
