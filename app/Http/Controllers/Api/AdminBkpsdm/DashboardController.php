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

        // Trend sertifikat per bulan (6 bulan terakhir)
        $trendSertifikat = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = \Carbon\Carbon::now()->subMonths($i);
            $count = \App\Models\Sertifikat::whereYear('diterbitkan_pada', $month->year)
                                            ->whereMonth('diterbitkan_pada', $month->month)
                                            ->count();
            $trendSertifikat[] = [
                'bulan' => $month->format('M'),
                'jumlah' => $count
            ];
        }

        // Recent courses (latest 5 regardless of status)
        $recentCourses = \App\Models\Pembelajaran::orderBy('dibuat_pada', 'desc')->take(5)->get();

        return response()->json([
            'message' => 'Dashboard Admin BKPSDM',
            'data' => [
                'statistik' => [
                    'total_peserta' => $totalPeserta,
                    'user_aktif' => $userAktif,
                    'total_komunitas' => $totalKomunitas,
                    'sertifikat_terverifikasi' => $totalSertifikat,
                    'persentase_keaktifan_komunitas' => 0, // Placeholder
                ],
                'trend_sertifikat' => $trendSertifikat,
                'recent_courses' => $recentCourses
            ]
        ]);
    }
}
