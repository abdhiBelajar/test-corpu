<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Kuis;
use App\Models\SoalKuis;
use App\Models\RiwayatKuis;
use App\Models\PendaftaranPembelajaran;
use App\Models\Materi;

class KuisController extends Controller
{
    public function show(Request $request, $id, $modul_id, $kuis_id)
    {
        $user = $request->user();

        // Cari pendaftaran
        $pendaftaran = PendaftaranPembelajaran::where('pengguna_id', $user->pengguna_id)
            ->where('pembelajaran_id', $id)
            ->first();

        if (!$pendaftaran) {
            return response()->json(['message' => 'Anda belum terdaftar di pelatihan ini'], 400);
        }

        $kuis = Kuis::with('soalKuis')->where('modul_id', $modul_id)->where('kuis_id', $kuis_id)->first();

        if (!$kuis) {
            return response()->json(['message' => 'Kuis tidak tersedia'], 404);
        }

        // Cek percobaan
        $percobaanKe = RiwayatKuis::where('pendaftaran_id', $pendaftaran->pendaftaran_id)
            ->where('kuis_id', $kuis->kuis_id)
            ->count();

        if ($percobaanKe >= $kuis->maks_percobaan && !RiwayatKuis::where('pendaftaran_id', $pendaftaran->pendaftaran_id)->where('kuis_id', $kuis->kuis_id)->where('apakah_lulus', true)->exists()) {
            return response()->json(['message' => 'Anda telah mencapai batas maksimal percobaan Kuis (' . $kuis->maks_percobaan . ' kali)'], 400);
        }

        // Format soal untuk dikirim
        $soal = $kuis->soalKuis->map(function($s) {
            $pilihan = is_string($s->pilihan_jawaban_json) ? json_decode($s->pilihan_jawaban_json, true) : $s->pilihan_jawaban_json;
            return [
                'soal_kuis_id' => $s->soal_kuis_id,
                'teks_soal' => $s->teks_soal,
                'pilihan_jawaban' => $pilihan
            ];
        });

        if ($kuis->acak_soal) {
            $soal = $soal->shuffle();
        }

        return response()->json([
            'message' => 'Soal Kuis berhasil diambil',
            'data' => [
                'kuis_id' => $kuis->kuis_id,
                'judul_kuis' => $kuis->judul_kuis,
                'judul_modul' => $kuis->modul->judul_modul,
                'nilai_kelulusan' => $kuis->nilai_kelulusan,
                'durasi_menit' => $kuis->durasi_menit,
                'percobaan_sekarang' => $percobaanKe + 1,
                'maks_percobaan' => $kuis->maks_percobaan,
                'soal' => $soal->values()
            ]
        ]);
    }

    public function submit(Request $request, $id, $modul_id, $kuis_id)
    {
        $request->validate([
            'jawaban' => 'required|array'
        ]);

        $user = $request->user();

        // Cari pendaftaran
        $pendaftaran = PendaftaranPembelajaran::where('pengguna_id', $user->pengguna_id)
            ->where('pembelajaran_id', $id)
            ->first();

        if (!$pendaftaran) {
            return response()->json(['message' => 'Anda belum terdaftar di pelatihan ini'], 400);
        }

        $kuis = Kuis::with('soalKuis')->where('modul_id', $modul_id)->where('kuis_id', $kuis_id)->first();

        if (!$kuis) {
            return response()->json(['message' => 'Kuis tidak ditemukan'], 404);
        }

        // Hitung nilai
        $jawabanUser = $request->jawaban; // array of ['soal_kuis_id' => x, 'jawaban' => 'A']
        $soalList = $kuis->soalKuis->keyBy('soal_kuis_id');

        $totalBobot = 0;
        $skorDidapat = 0;

        foreach ($jawabanUser as $j) {
            $soalId = $j['soal_kuis_id'] ?? null;
            $jawaban = $j['jawaban'] ?? null;

            if ($soalId && isset($soalList[$soalId])) {
                $s = $soalList[$soalId];
                $totalBobot += $s->bobot_nilai;

                if ($s->kunci_jawaban === $jawaban) {
                    $skorDidapat += $s->bobot_nilai;
                }
            }
        }

        $allBobot = $kuis->soalKuis->sum('bobot_nilai');
        $nilaiAkhir = $allBobot > 0 ? ($skorDidapat / $allBobot) * 100 : 0;
        $apakahLulus = $nilaiAkhir >= $kuis->nilai_kelulusan;

        $percobaanKe = RiwayatKuis::where('pendaftaran_id', $pendaftaran->pendaftaran_id)
            ->where('kuis_id', $kuis->kuis_id)
            ->count() + 1;

        // Simpan Riwayat
        $riwayat = RiwayatKuis::create([
            'kuis_id' => $kuis->kuis_id,
            'pendaftaran_id' => $pendaftaran->pendaftaran_id,
            'percobaan_ke' => $percobaanKe,
            'nilai' => $nilaiAkhir,
            'apakah_lulus' => $apakahLulus,
            'jawaban_peserta_json' => $jawabanUser,
            'snapshot_soal_json' => $kuis->soalKuis->toArray()
        ]);

        if ($apakahLulus) {
            // Update progress pembelajaran jika lulus kuis
            $this->updateProgress($pendaftaran, $id);
        }

        return response()->json([
            'message' => 'Kuis berhasil disubmit',
            'data' => [
                'nilai' => $nilaiAkhir,
                'apakah_lulus' => $apakahLulus,
                'percobaan_ke' => $percobaanKe,
                'maks_percobaan' => $kuis->maks_percobaan,
                'sisa_percobaan' => max(0, $kuis->maks_percobaan - $percobaanKe)
            ]
        ]);
    }

    private function updateProgress($pendaftaran, $id)
    {
        $totalMateri = Materi::whereHas('modul', function($q) use ($id) {
            $q->where('pembelajaran_id', $id);
        })->count();

        $totalKuis = Kuis::whereHas('modul', function($q) use ($id) {
            $q->where('pembelajaran_id', $id);
        })->count();

        $materiSelesai = \App\Models\ProgresMateri::where('pendaftaran_id', $pendaftaran->pendaftaran_id)
            ->where('apakah_selesai', true)
            ->count();

        $kuisLulus = RiwayatKuis::where('pendaftaran_id', $pendaftaran->pendaftaran_id)
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
    }
}
