<?php

namespace App\Http\Controllers\Api\AdminKomunitas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class KuisController extends Controller
{
    private function isPembelajaranAdmin($user, $pembelajaran_id) {
        $pembelajaran = \App\Models\Pembelajaran::find($pembelajaran_id);
        if (!$pembelajaran) return false;

        return \App\Models\AdminKomunitas::where('pengguna_id', $user->pengguna_id)
                    ->where('komunitas_id', $pembelajaran->komunitas_id)
                    ->exists();
    }

    public function index(Request $request, $modul_id)
    {
        $modul = \App\Models\Modul::findOrFail($modul_id);
        
        if (!$this->isPembelajaranAdmin($request->user(), $modul->pembelajaran_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $kuis = \App\Models\Kuis::where('modul_id', $modul_id)->with('soalKuis')->first();

        return response()->json([
            'message' => 'Kuis Modul berhasil diambil',
            'data' => $kuis
        ]);
    }

    public function store(Request $request, $modul_id)
    {
        $modul = \App\Models\Modul::findOrFail($modul_id);
        
        if (!$this->isPembelajaranAdmin($request->user(), $modul->pembelajaran_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $pembelajaran = \App\Models\Pembelajaran::find($modul->pembelajaran_id);
        if ($pembelajaran->status === 'dipublikasikan') {
            return response()->json(['message' => 'Tidak dapat menambah kuis ke pembelajaran yang sudah dipublikasikan.'], 400);
        }

        if (\App\Models\Kuis::where('modul_id', $modul_id)->exists()) {
            return response()->json(['message' => 'Modul ini sudah memiliki Kuis. Gunakan endpoint update.'], 400);
        }

        $request->validate([
            'judul_kuis' => 'required|string|max:200',
            'nilai_kelulusan' => 'required|numeric|min:0|max:100',
            'maks_percobaan' => 'nullable|integer|min:1',
            'acak_soal' => 'nullable|boolean',
            'tampilkan_kunci_setelah' => 'nullable|boolean',
            'soal' => 'nullable|array',
            'soal.*.teks_soal' => 'required|string',
            'soal.*.pilihan_jawaban_json' => 'required|array', // expected to be sent as assoc array/object
            'soal.*.kunci_jawaban' => 'required|string|max:10',
            'soal.*.bobot_nilai' => 'nullable|numeric|min:0',
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $kuis = \App\Models\Kuis::create([
                'modul_id' => $modul_id,
                'judul_kuis' => $request->judul_kuis,
                'nilai_kelulusan' => $request->nilai_kelulusan,
                'maks_percobaan' => $request->has('maks_percobaan') ? $request->maks_percobaan : 3,
                'acak_soal' => $request->has('acak_soal') ? $request->acak_soal : true,
                'tampilkan_kunci_setelah' => $request->has('tampilkan_kunci_setelah') ? $request->tampilkan_kunci_setelah : true,
            ]);

            if ($request->has('soal') && is_array($request->soal)) {
                foreach ($request->soal as $item) {
                    \App\Models\SoalKuis::create([
                        'kuis_id' => $kuis->kuis_id,
                        'teks_soal' => $item['teks_soal'],
                        'pilihan_jawaban_json' => json_encode($item['pilihan_jawaban_json']),
                        'kunci_jawaban' => $item['kunci_jawaban'],
                        'bobot_nilai' => $item['bobot_nilai'] ?? 1,
                    ]);
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'message' => 'Kuis dan soal berhasil ditambahkan',
                'data' => $kuis->load('soalKuis')
            ], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['message' => 'Gagal menyimpan kuis: ' . $e->getMessage()], 500);
        }
    }

    public function show(Request $request, $id)
    {
        $kuis = \App\Models\Kuis::with('soalKuis')->findOrFail($id);
        $modul = \App\Models\Modul::find($kuis->modul_id);

        if (!$this->isPembelajaranAdmin($request->user(), $modul->pembelajaran_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        return response()->json([
            'message' => 'Detail Kuis berhasil diambil',
            'data' => $kuis
        ]);
    }

    public function update(Request $request, $id)
    {
        $kuis = \App\Models\Kuis::findOrFail($id);
        $modul = \App\Models\Modul::find($kuis->modul_id);

        if (!$this->isPembelajaranAdmin($request->user(), $modul->pembelajaran_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $pembelajaran = \App\Models\Pembelajaran::find($modul->pembelajaran_id);
        if ($pembelajaran->status === 'dipublikasikan') {
            return response()->json(['message' => 'Tidak dapat mengubah kuis pada pembelajaran yang sudah dipublikasikan.'], 400);
        }

        $request->validate([
            'judul_kuis' => 'sometimes|string|max:200',
            'nilai_kelulusan' => 'sometimes|numeric|min:0|max:100',
            'maks_percobaan' => 'sometimes|integer|min:1',
            'acak_soal' => 'sometimes|boolean',
            'tampilkan_kunci_setelah' => 'sometimes|boolean',
            'soal' => 'nullable|array', // jika dikirim, akan mereplace/sync seluruh soal
            'soal.*.teks_soal' => 'required|string',
            'soal.*.pilihan_jawaban_json' => 'required|array',
            'soal.*.kunci_jawaban' => 'required|string|max:10',
            'soal.*.bobot_nilai' => 'nullable|numeric|min:0',
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $kuis->update($request->only([
                'judul_kuis', 'nilai_kelulusan', 'maks_percobaan', 'acak_soal', 'tampilkan_kunci_setelah'
            ]));

            if ($request->has('soal') && is_array($request->soal)) {
                // Untuk kesederhanaan draf, kita hapus semua soal lama dan insert yang baru
                // (Kecuali jika Kuis sudah pernah dikerjakan, maka tidak boleh dihapus - tapi asumsinya ini belum dipublikasikan)
                \App\Models\SoalKuis::where('kuis_id', $kuis->kuis_id)->delete();

                foreach ($request->soal as $item) {
                    \App\Models\SoalKuis::create([
                        'kuis_id' => $kuis->kuis_id,
                        'teks_soal' => $item['teks_soal'],
                        'pilihan_jawaban_json' => json_encode($item['pilihan_jawaban_json']),
                        'kunci_jawaban' => $item['kunci_jawaban'],
                        'bobot_nilai' => $item['bobot_nilai'] ?? 1,
                    ]);
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'message' => 'Kuis berhasil diperbarui',
                'data' => $kuis->fresh('soalKuis')
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['message' => 'Gagal memperbarui kuis: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $kuis = \App\Models\Kuis::findOrFail($id);
        $modul = \App\Models\Modul::find($kuis->modul_id);

        if (!$this->isPembelajaranAdmin($request->user(), $modul->pembelajaran_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $pembelajaran = \App\Models\Pembelajaran::find($modul->pembelajaran_id);
        if ($pembelajaran->status === 'dipublikasikan') {
            return response()->json(['message' => 'Tidak dapat menghapus kuis pada pembelajaran yang sudah dipublikasikan.'], 400);
        }

        $kuis->delete();

        return response()->json([
            'message' => 'Kuis berhasil dihapus'
        ]);
    }

}

