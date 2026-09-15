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

        // Evaluasi locking silabus
        $isPreviousModulePassed = true;
        $mappedModul = $pembelajaran->modul->map(function ($m) use ($progresMateri, $pendaftaran, &$isPreviousModulePassed) {
            $isModuleLocked = !$isPreviousModulePassed;

            $isPreviousMateriDone = true;
            $allMateriInThisModulDone = true;

            $materiList = $m->materi->map(function ($mat) use ($progresMateri, $pendaftaran, $isModuleLocked, &$isPreviousMateriDone, &$allMateriInThisModulDone) {
                $isRead = isset($progresMateri[$mat->materi_id]) && $progresMateri[$mat->materi_id] ? true : false;
                $isLocked = $isModuleLocked || !$isPreviousMateriDone;

                if (!$isRead) {
                    $isPreviousMateriDone = false;
                    $allMateriInThisModulDone = false;
                }

                return [
                    'materi_id' => $mat->materi_id,
                    'judul' => $mat->judul_materi,
                    'tipe' => $mat->tipe_materi,
                    'tautan' => ($pendaftaran && !$isLocked) ? $mat->tautan_atau_berkas : null,
                    'durasi' => $mat->durasi_menit,
                    'is_read' => $isRead,
                    'is_locked' => $isLocked
                ];
            });

            $isKuisCompleted = false;
            $isKuisLocked = true;
            if ($m->kuis) {
                $isKuisCompleted = $pendaftaran ? \App\Models\RiwayatKuis::where('pendaftaran_id', $pendaftaran->pendaftaran_id)
                    ->where('kuis_id', $m->kuis->kuis_id)
                    ->where('apakah_lulus', true)
                    ->exists() : false;
                
                // Kuis terbuka hanya jika seluruh materi pada modul ini sudah dibaca
                $isKuisLocked = $isModuleLocked || !$allMateriInThisModulDone;
            }

            // Status kelulusan modul untuk menentukan apakah modul berikutnya terbuka
            if ($m->kuis) {
                $isPreviousModulePassed = $allMateriInThisModulDone && $isKuisCompleted;
            } else {
                $isPreviousModulePassed = $allMateriInThisModulDone;
            }

            return [
                'modul_id' => $m->modul_id,
                'judul' => $m->judul_modul,
                'urutan' => $m->urutan,
                'is_locked' => $isModuleLocked,
                'materi' => $materiList,
                'kuis' => $m->kuis ? [
                    'kuis_id' => $m->kuis->kuis_id,
                    'judul' => $m->kuis->judul_kuis,
                    'durasi' => $m->kuis->durasi_menit,
                    'is_completed' => $isKuisCompleted,
                    'is_locked' => $isKuisLocked
                ] : null
            ];
        });

        $data = [
            'pembelajaran_id' => $pembelajaran->pembelajaran_id,
            'judul' => $pembelajaran->judul_pembelajaran,
            'deskripsi' => $pembelajaran->deskripsi,
            'kategori' => $pembelajaran->kategori,
            'jpl' => $jpDet ? $jpDet->jp_final : 0,
            'is_enrolled' => $pendaftaran ? true : false,
            'status_pendaftaran' => $pendaftaran->status_pendaftaran ?? null,
            'progress' => $pendaftaran->persentase_progres ?? 0,
            'modul' => $mappedModul,
            'post_test' => $pembelajaran->postTest ? [
                'post_test_id' => $pembelajaran->postTest->post_test_id,
                'judul' => 'Post Test Akhir Pelatihan',
                'durasi' => $pembelajaran->postTest->durasi_menit,
                'is_locked' => ($pendaftaran->persentase_progres ?? 0) < 100
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

        // Validasi keberadaan materi dan kecocokan pembelajaran
        $materi = Materi::with('modul')->find($materi_id);
        if (!$materi || $materi->modul->pembelajaran_id != $id) {
            return response()->json(['message' => 'Materi tidak ditemukan dalam pelatihan ini'], 404);
        }

        $currentModul = $materi->modul;

        // 1. Validasi urutan modul: Semua modul dengan urutan lebih kecil harus sudah selesai materi & kuisnya
        $prevModuls = \App\Models\Modul::where('pembelajaran_id', $id)
            ->where('urutan', '<', $currentModul->urutan)
            ->with(['materi', 'kuis'])
            ->get();

        foreach ($prevModuls as $pm) {
            $pmMateriIds = $pm->materi->pluck('materi_id');
            $pmMateriDone = ProgresMateri::where('pendaftaran_id', $pendaftaran->pendaftaran_id)
                ->whereIn('materi_id', $pmMateriIds)
                ->where('apakah_selesai', true)
                ->count();

            if ($pmMateriIds->count() > 0 && $pmMateriDone < $pmMateriIds->count()) {
                return response()->json([
                    'message' => 'Anda harus menyelesaikan materi pada modul sebelumnya terlebih dahulu.'
                ], 422);
            }

            if ($pm->kuis) {
                $isKuisPassed = \App\Models\RiwayatKuis::where('pendaftaran_id', $pendaftaran->pendaftaran_id)
                    ->where('kuis_id', $pm->kuis->kuis_id)
                    ->where('apakah_lulus', true)
                    ->exists();

                if (!$isKuisPassed) {
                    return response()->json([
                        'message' => 'Anda harus lulus kuis pada modul sebelumnya terlebih dahulu.'
                    ], 422);
                }
            }
        }

        // 2. Validasi urutan materi dalam modul yang sama: Seluruh materi dengan urutan lebih kecil harus selesai
        $prevMateriList = Materi::where('modul_id', $currentModul->modul_id)
            ->where('urutan', '<', $materi->urutan)
            ->pluck('materi_id');

        if ($prevMateriList->count() > 0) {
            $prevMateriDone = ProgresMateri::where('pendaftaran_id', $pendaftaran->pendaftaran_id)
                ->whereIn('materi_id', $prevMateriList)
                ->where('apakah_selesai', true)
                ->count();

            if ($prevMateriDone < $prevMateriList->count()) {
                return response()->json([
                    'message' => 'Anda harus menyelesaikan materi sebelumnya sesuai urutan silabus.'
                ], 422);
            }
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
