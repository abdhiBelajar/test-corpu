<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pembelajaran;
use App\Models\PendaftaranPembelajaran;
use App\Models\ProgresMateri;
use App\Models\Materi;
use App\Models\UlasanPembelajaran;

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
            'pembelajaranJp'
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
        $jpDet = $pembelajaran->pembelajaranJp->first();
        $data = [
            'pembelajaran_id' => $pembelajaran->pembelajaran_id,
            'judul' => $pembelajaran->judul_pembelajaran,
            'deskripsi' => $pembelajaran->deskripsi,
            'kategori' => $pembelajaran->kategori,
            'jpl' => $jpDet ? $jpDet->jp_final : 0,
            'is_enrolled' => $pendaftaran ? true : false,
            'status_pendaftaran' => $pendaftaran->status_pendaftaran ?? null,
            'progress' => $pendaftaran->persentase_progres ?? 0,
            'modul' => $pembelajaran->modul->map(function ($m) use ($progresMateri, $pendaftaran) {
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
                        'durasi' => $m->kuis->durasi_menit,
                        'is_completed' => $pendaftaran ? \App\Models\RiwayatKuis::where('pendaftaran_id', $pendaftaran->pendaftaran_id)
                            ->where('kuis_id', $m->kuis->kuis_id)
                            ->where('apakah_lulus', true)
                            ->exists() : false
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
        // Total materi dan kuis dalam pembelajaran ini
        $totalMateri = Materi::whereHas('modul', function($q) use ($id) {
            $q->where('pembelajaran_id', $id);
        })->count();

        $totalKuis = \App\Models\Kuis::whereHas('modul', function($q) use ($id) {
            $q->where('pembelajaran_id', $id);
        })->count();

        $materiSelesai = ProgresMateri::where('pendaftaran_id', $pendaftaran->pendaftaran_id)
            ->where('apakah_selesai', true)
            ->count();

        $kuisLulus = \App\Models\RiwayatKuis::where('pendaftaran_id', $pendaftaran->pendaftaran_id)
            ->where('apakah_lulus', true)
            ->distinct('kuis_id')
            ->count('kuis_id');

        $totalItem = $totalMateri + $totalKuis;
        $itemSelesai = $materiSelesai + $kuisLulus;

        $persentase = $totalItem > 0 ? round(($itemSelesai / $totalItem) * 100) : 0;

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

    public function submitUlasan(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'ulasan' => 'required|string|max:1000'
        ]);

        $user = $request->user();

        $pendaftaran = PendaftaranPembelajaran::where('pengguna_id', $user->pengguna_id)
            ->where('pembelajaran_id', $id)
            ->first();

        if (!$pendaftaran) {
            return response()->json(['message' => 'Anda belum terdaftar di pelatihan ini'], 400);
        }

        // Cek apakah sudah pernah mengirim ulasan
        $existing = UlasanPembelajaran::where('pendaftaran_id', $pendaftaran->pendaftaran_id)->first();
        if ($existing) {
            return response()->json(['message' => 'Anda sudah memberikan ulasan untuk pelatihan ini'], 400);
        }

        UlasanPembelajaran::create([
            'pendaftaran_id' => $pendaftaran->pendaftaran_id,
            'skor_rating' => $request->rating,
            'teks_ulasan' => $request->ulasan
        ]);

        return response()->json(['message' => 'Ulasan berhasil dikirim']);
    }
}
