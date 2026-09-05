<?php

namespace App\Http\Controllers\Api\AdminBkpsdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalPeserta = \App\Models\Pengguna::where('peran', 'peserta')->count();
        $userAktif = \App\Models\Pengguna::where('peran', 'peserta')->where('status', 'aktif')->count();
        $totalKomunitas = \App\Models\Komunitas::count();
        $totalSertifikat = \App\Models\Sertifikat::count(); // Asumsikan semua tersinkron adalah terverifikasi

        return response()->json([
            'message' => 'Dashboard Admin BKPSDM',
            'data' => [
                'statistik' => [
                    'total_peserta' => $totalPeserta,
                    'user_aktif' => $userAktif,
                    'total_komunitas' => $totalKomunitas,
                    'sertifikat_terverifikasi' => $totalSertifikat,
                    'persentase_keaktifan_komunitas' => 0, // Placeholder
                ]
            ]
        ]);
    }
}
