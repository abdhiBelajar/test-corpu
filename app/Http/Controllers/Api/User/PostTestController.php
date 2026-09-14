<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PostTest;
use App\Models\SoalPostTest;
use App\Models\RiwayatPostTest;
use App\Models\PendaftaranPembelajaran;
use App\Models\Sertifikat;

class PostTestController extends Controller
{
    public function show(Request $request, $id)
    {
        $user = $request->user();

        // Cari pendaftaran
        $pendaftaran = PendaftaranPembelajaran::where('pengguna_id', $user->pengguna_id)
            ->where('pembelajaran_id', $id)
            ->first();

        if (!$pendaftaran) {
            return response()->json(['message' => 'Anda belum terdaftar di pelatihan ini'], 400);
        }

        if ($pendaftaran->persentase_progres < 100) {
            return response()->json(['message' => 'Anda harus menyelesaikan semua materi terlebih dahulu'], 400);
        }

        $postTest = PostTest::with('soalPostTest')->where('pembelajaran_id', $id)->first();

        if (!$postTest) {
            return response()->json(['message' => 'Post Test tidak tersedia untuk pelatihan ini'], 404);
        }

        // Cek percobaan
        $percobaanKe = RiwayatPostTest::where('pendaftaran_id', $pendaftaran->pendaftaran_id)
            ->where('post_test_id', $postTest->post_test_id)
            ->count();

        if ($percobaanKe >= $postTest->maks_percobaan && !RiwayatPostTest::where('pendaftaran_id', $pendaftaran->pendaftaran_id)->where('post_test_id', $postTest->post_test_id)->where('apakah_lulus', true)->exists()) {
            return response()->json(['message' => 'Anda telah mencapai batas maksimal percobaan Post Test (' . $postTest->maks_percobaan . ' kali)'], 400);
        }

        if ($pendaftaran->status_pendaftaran === 'lulus' || $pendaftaran->status_pendaftaran === 'selesai') {
            return response()->json(['message' => 'Anda sudah lulus pelatihan ini'], 400);
        }

        // Format soal untuk dikirim
        $soal = $postTest->soalPostTest->map(function($s) {
            $pilihan = is_string($s->pilihan_jawaban_json) ? json_decode($s->pilihan_jawaban_json, true) : $s->pilihan_jawaban_json;
            return [
                'soal_post_test_id' => $s->soal_post_test_id,
                'teks_soal' => $s->teks_soal,
                'pilihan_jawaban' => $pilihan
            ];
        });

        if ($postTest->acak_soal) {
            $soal = $soal->shuffle();
        }

        return response()->json([
            'message' => 'Soal Post Test berhasil diambil',
            'data' => [
                'post_test_id' => $postTest->post_test_id,
                'judul_pembelajaran' => $postTest->pembelajaran->judul_pembelajaran,
                'nilai_kelulusan' => $postTest->nilai_kelulusan,
                'durasi_menit' => $postTest->durasi_menit,
                'percobaan_sekarang' => $percobaanKe + 1,
                'maks_percobaan' => $postTest->maks_percobaan,
                'soal' => $soal->values()
            ]
        ]);
    }

    public function submit(Request $request, $id)
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

        $postTest = PostTest::with('soalPostTest')->where('pembelajaran_id', $id)->first();

        if (!$postTest) {
            return response()->json(['message' => 'Post Test tidak ditemukan'], 404);
        }

        // Hitung nilai
        $jawabanUser = $request->jawaban; // array of ['soal_post_test_id' => x, 'jawaban' => 'A']
        $soalList = $postTest->soalPostTest->keyBy('soal_post_test_id');

        $totalBobot = 0;
        $skorDidapat = 0;

        foreach ($jawabanUser as $j) {
            $soalId = $j['soal_post_test_id'] ?? null;
            $jawaban = $j['jawaban'] ?? null;

            if ($soalId && isset($soalList[$soalId])) {
                $s = $soalList[$soalId];
                $totalBobot += $s->bobot_nilai;

                if ($s->kunci_jawaban === $jawaban) {
                    $skorDidapat += $s->bobot_nilai;
                }
            }
        }

        // Calculate final score based on total questions bobot if some are missed
        $allBobot = $postTest->soalPostTest->sum('bobot_nilai');
        $nilaiAkhir = $allBobot > 0 ? ($skorDidapat / $allBobot) * 100 : 0;
        $apakahLulus = $nilaiAkhir >= $postTest->nilai_kelulusan;

        $percobaanKe = RiwayatPostTest::where('pendaftaran_id', $pendaftaran->pendaftaran_id)
            ->where('post_test_id', $postTest->post_test_id)
            ->count() + 1;

        // Simpan Riwayat
        $riwayat = RiwayatPostTest::create([
            'post_test_id' => $postTest->post_test_id,
            'pendaftaran_id' => $pendaftaran->pendaftaran_id,
            'percobaan_ke' => $percobaanKe,
            'nilai' => $nilaiAkhir,
            'apakah_lulus' => $apakahLulus,
            'jawaban_peserta_json' => $jawabanUser,
            'snapshot_soal_json' => $postTest->soalPostTest->toArray()
        ]);

        if ($apakahLulus) {
            // Update pendaftaran lulus
            $pendaftaran->status_pendaftaran = 'lulus';
            $pendaftaran->diselesaikan_pada = now();
            $pendaftaran->save();

            // Generate Sertifikat
            Sertifikat::firstOrCreate([
                'pendaftaran_id' => $pendaftaran->pendaftaran_id
            ], [
                'nomor_sertifikat' => 'CERT-' . strtoupper(uniqid()),
                'nama_lengkap_snapshot' => $user->nama_lengkap,
                'nip_snapshot' => $user->nip,
                'tanggal_terbit' => now(),
                'tautan_berkas' => '-'
            ]);
        } else {
            // Jika tidak lulus dan sudah max percobaan
            if ($percobaanKe >= $postTest->maks_percobaan) {
                // Biarkan status_pendaftaran atau ubah ke gagal?
                // Kita asumsikan tetap 'menunggu_post_test' tapi dilarang ambil lagi
                // Atau bisa status khusus. Sesuai request, biarkan saja.
            }
        }

        $pembelajaran = $postTest->pembelajaran;
        $jpDet = $pembelajaran->pembelajaranJp->first();
        $jpl = $jpDet ? $jpDet->jp_final : 0;

        $responseData = [
            'nilai' => $nilaiAkhir,
            'apakah_lulus' => $apakahLulus,
            'percobaan_ke' => $percobaanKe,
            'maks_percobaan' => $postTest->maks_percobaan,
            'sisa_percobaan' => max(0, $postTest->maks_percobaan - $percobaanKe)
        ];

        if ($apakahLulus) {
            $sertifikatModel = Sertifikat::where('pendaftaran_id', $pendaftaran->pendaftaran_id)->first();
            $responseData['sertifikat'] = [
                'sertifikat_id' => $sertifikatModel->sertifikat_id ?? null,
                'judul_pembelajaran' => $pembelajaran->judul_pembelajaran,
                'nama_peserta' => $user->nama_lengkap,
                'nip' => $user->nip,
                'jpl' => $jpl,
                'tanggal' => now()->translatedFormat('d F Y')
            ];
        }

        return response()->json([
            'message' => 'Post Test berhasil disubmit',
            'data' => $responseData
        ]);
    }
}
