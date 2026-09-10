<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pembelajaran;
use App\Models\PendaftaranPembelajaran;
use App\Models\ProgresMateri;
use App\Models\Materi;

class CourseDetailController extends Controller
{
    public function show(Request $request, $id)
    {
        $user = $request->user();

        // Ambil data pelatihan beserta relasinya
        $pembelajaran = Pembelajaran::with([
            'modul' => function ($q) {
                $q->orderBy('urutan', 'asc')->with(['materi', 'kuis']);
            },
            'postTest',
            'jp'
        ])->findOrFail($id);

        // Cek pendaftaran
        $pendaftaran = PendaftaranPembelajaran::where('pengguna_id', $user->pengguna_id)
            ->where('pembelajaran_id', $id)
            ->first();

        $progresMateri = [];
        if ($pendaftaran) {
            $progresMateri = ProgresMateri::where('pendaftaran_id', $pendaftaran->pendaftaran_id)
                ->pluck('apakah_selesai', 'materi_id')
                ->toArray();
        }

        // Map data agar mudah dikonsumsi frontend
        $data = [
            'pembelajaran_id' => $pembelajaran->pembelajaran_id,
            'judul' => $pembelajaran->judul_pembelajaran,
            'deskripsi' => $pembelajaran->deskripsi,
            'kategori' => $pembelajaran->kategori,
            'jpl' => $pembelajaran->jp->jp_final ?? 0,
            'is_enrolled' => $pendaftaran ? true : false,
            'status_pendaftaran' => $pendaftaran->status_pendaftaran ?? null,
            'progress' => $pendaftaran->persentase_progres ?? 0,
            'modul' => $pembelajaran->modul->map(function ($m) use ($progresMateri) {
                return [
                    'modul_id' => $m->modul_id,
                    'judul' => $m->judul_modul,
                    'urutan' => $m->urutan,
                    'materi' => $m->materi->map(function ($mat) use ($progresMateri) {
                        return [
                            'materi_id' => $mat->materi_id,
                            'judul' => $mat->judul_materi,
                            'tipe' => $mat->tipe_materi,
                            'tautan' => $mat->tautan_atau_berkas,
                            'durasi' => $mat->durasi_menit,
                            'is_read' => isset($progresMateri[$mat->materi_id]) && $progresMateri[$mat->materi_id] ? true : false
                        ];
                    }),
                    'kuis' => $m->kuis ? [
                        'kuis_id' => $m->kuis->kuis_id,
                        'judul' => $m->kuis->judul_kuis,
                        'durasi' => $m->kuis->durasi_menit
                    ] : null
                ];
            }),
            'post_test' => $pembelajaran->postTest ? [
                'post_test_id' => $pembelajaran->postTest->post_test_id,
                'judul' => 'Post Test Akhir Pelatihan',
                'durasi' => $pembelajaran->postTest->durasi_menit
            ] : null
        ];

        return response()->json([
            'message' => 'Detail pelatihan berhasil diambil',
            'data' => $data
        ]);
    }

    public function markMateriAsRead(Request $request, $id, $materi_id)
    {
        $user = $request->user();

        $pendaftaran = PendaftaranPembelajaran::where('pengguna_id', $user->pengguna_id)
            ->where('pembelajaran_id', $id)
            ->first();

        if (!$pendaftaran) {
            return response()->json(['message' => 'Anda belum terdaftar di pelatihan ini'], 400);
        }

        // Tandai sebagai selesai
        ProgresMateri::updateOrCreate(
            [
                'pendaftaran_id' => $pendaftaran->pendaftaran_id,
                'materi_id' => $materi_id
            ],
            [
                'apakah_selesai' => true,
                'diselesaikan_pada' => now()
            ]
        );

        // Hitung ulang persentase progres
        // Total materi wajib dalam pembelajaran ini
        $totalMateri = Materi::whereHas('modul', function($q) use ($id) {
            $q->where('pembelajaran_id', $id);
        })->count();

        $materiSelesai = ProgresMateri::where('pendaftaran_id', $pendaftaran->pendaftaran_id)
            ->where('apakah_selesai', true)
            ->count();

        $persentase = $totalMateri > 0 ? round(($materiSelesai / $totalMateri) * 100) : 0;

        $pendaftaran->persentase_progres = $persentase;

        // Jika progres mencapai 100%, ubah status jika belum menunggu post test
        if ($persentase >= 100 && in_array($pendaftaran->status_pendaftaran, ['terdaftar', 'sedang_berjalan'])) {
            $pendaftaran->status_pendaftaran = 'menunggu_post_test';
        } elseif ($persentase > 0 && $pendaftaran->status_pendaftaran === 'terdaftar') {
            $pendaftaran->status_pendaftaran = 'sedang_berjalan';
        }

        $pendaftaran->save();

        return response()->json([
            'message' => 'Materi berhasil ditandai selesai',
            'data' => [
                'progress' => $persentase,
                'status_pendaftaran' => $pendaftaran->status_pendaftaran
            ]
        ]);
    }
}
