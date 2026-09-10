<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PendaftaranPembelajaran;
use App\Models\Pembelajaran;
use App\Models\Sertifikat;
use App\Models\PembelajaranJp;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $penggunaId = $user->pengguna_id;

        // Semua pendaftaran user ini
        $pendaftaran = PendaftaranPembelajaran::where('pengguna_id', $penggunaId)
            ->with(['pembelajaran' => function($q) {
                $q->withCount('modul')->with('jp');
            }])
            ->get();

        $aktif = $pendaftaran->whereIn('status_pendaftaran', ['terdaftar', 'sedang_berjalan', 'menunggu_post_test'])->count();
        $selesai = $pendaftaran->whereIn('status_pendaftaran', ['selesai', 'lulus'])->count();

        // Total JPL dari pelatihan yang selesai/lulus
        $pembelajaranIdsSelesai = $pendaftaran->whereIn('status_pendaftaran', ['selesai', 'lulus'])->pluck('pembelajaran_id');
        $totalJpl = PembelajaranJp::whereIn('pembelajaran_id', $pembelajaranIdsSelesai)->sum('jp_final');

        // Sertifikat
        $sertifikat = Sertifikat::whereHas('pendaftaran', function($q) use ($penggunaId) {
            $q->where('pengguna_id', $penggunaId);
        })->count();

        // Current Course (terakhir diakses/didaftar)
        $currentCourseReg = PendaftaranPembelajaran::where('pengguna_id', $penggunaId)
            ->whereIn('status_pendaftaran', ['terdaftar', 'sedang_berjalan'])
            ->with(['pembelajaran' => function($q) {
                $q->withCount('modul')->with('jp');
            }])
            ->orderBy('terdaftar_pada', 'desc')
            ->first();

        $currentCourse = null;
        if ($currentCourseReg && $currentCourseReg->pembelajaran) {
            $currentCourse = [
                'pendaftaran_id' => $currentCourseReg->pendaftaran_id,
                'pembelajaran_id' => $currentCourseReg->pembelajaran->pembelajaran_id,
                'judul' => $currentCourseReg->pembelajaran->judul_pembelajaran,
                'kategori' => $currentCourseReg->pembelajaran->kategori,
                'progress' => $currentCourseReg->persentase_progres,
                'total_modul' => $currentCourseReg->pembelajaran->modul_count,
                'jpl' => $currentCourseReg->pembelajaran->jp->jp_final ?? 0,
                'next_module' => 'Lanjutkan ke Modul', // This could be dynamically resolved if needed
                'image' => 'https://images.unsplash.com/photo-1542744173-8e7e53415bb0?q=80&w=2070&auto=format&fit=crop'
            ];
        }

        // Rekomendasi (Kursus yang dipublikasikan dan belum diikuti)
        $enrolledIds = $pendaftaran->pluck('pembelajaran_id');
        $rekomendasi = Pembelajaran::where('status', 'dipublikasikan')
            ->whereNotIn('pembelajaran_id', $enrolledIds)
            ->withCount('modul')
            ->with('jp')
            ->latest('dipublikasikan_pada')
            ->take(3)
            ->get()
            ->map(function ($c) {
                return [
                    'pembelajaran_id' => $c->pembelajaran_id,
                    'judul' => $c->judul_pembelajaran,
                    'jpl' => $c->jp->jp_final ?? 0,
                    'total_modul' => $c->modul_count,
                    'image' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?q=80&w=2070&auto=format&fit=crop'
                ];
            });

        // Aktivitas Terakhir
        $activities = [];
        foreach($pendaftaran->sortByDesc('terdaftar_pada')->take(3) as $p) {
            $activities[] = [
                'type' => $p->status_pendaftaran,
                'title' => 'Mendaftar pelatihan ' . ($p->pembelajaran->judul_pembelajaran ?? 'Pelatihan'),
                'time' => $p->terdaftar_pada ? \Carbon\Carbon::parse($p->terdaftar_pada)->diffForHumans() : 'Baru saja'
            ];
        }

        return response()->json([
            'message' => 'Dashboard user berhasil diambil',
            'data' => [
                'stats' => [
                    'aktif' => $aktif,
                    'selesai' => $selesai,
                    'total_jpl' => $totalJpl,
                    'sertifikat' => $sertifikat,
                ],
                'current_course' => $currentCourse,
                'rekomendasi' => $rekomendasi,
                'activities' => $activities
            ]
        ]);
    }
}
