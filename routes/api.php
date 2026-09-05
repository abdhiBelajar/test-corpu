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
Route::post('/reset-password-default', [AuthController::class, 'resetPasswordToDefault']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
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
