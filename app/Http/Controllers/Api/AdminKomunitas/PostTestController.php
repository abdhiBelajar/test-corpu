<?php

namespace App\Http\Controllers\Api\AdminKomunitas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PostTestController extends Controller
{
    private function isPembelajaranAdmin($user, $pembelajaran_id) {
        $pembelajaran = \App\Models\Pembelajaran::find($pembelajaran_id);
        if (!$pembelajaran) return false;

        return \App\Models\AdminKomunitas::where('pengguna_id', $user->pengguna_id)
                    ->where('komunitas_id', $pembelajaran->komunitas_id)
                    ->exists();
    }

    public function index(Request $request, $pembelajaran_id)
    {
        if (!$this->isPembelajaranAdmin($request->user(), $pembelajaran_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $postTest = \App\Models\PostTest::where('pembelajaran_id', $pembelajaran_id)->with('soalPostTest')->first();

        return response()->json([
            'message' => 'Post Test berhasil diambil',
            'data' => $postTest
        ]);
    }

    public function store(Request $request, $pembelajaran_id)
    {
        if (!$this->isPembelajaranAdmin($request->user(), $pembelajaran_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $pembelajaran = \App\Models\Pembelajaran::find($pembelajaran_id);
        if ($pembelajaran->status === 'dipublikasikan') {
            return response()->json(['message' => 'Tidak dapat menambah post test ke pembelajaran yang sudah dipublikasikan.'], 400);
        }

        if (\App\Models\PostTest::where('pembelajaran_id', $pembelajaran_id)->exists()) {
            return response()->json(['message' => 'Pembelajaran ini sudah memiliki Post Test. Gunakan endpoint update.'], 400);
        }

        $request->validate([
            'nilai_kelulusan' => 'required|numeric|min:0|max:100',
            'maks_percobaan' => 'nullable|integer|min:1',
            'acak_soal' => 'nullable|boolean',
            'tampilkan_kunci_setelah' => 'nullable|boolean',
            'durasi_menit' => 'nullable|integer|min:1',
            'soal' => 'nullable|array',
            'soal.*.teks_soal' => 'required|string',
            'soal.*.pilihan_jawaban_json' => 'required|array',
            'soal.*.kunci_jawaban' => 'required|string|max:10',
            'soal.*.bobot_nilai' => 'nullable|numeric|min:0',
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $postTest = \App\Models\PostTest::create([
                'pembelajaran_id' => $pembelajaran_id,
                'nilai_kelulusan' => $request->nilai_kelulusan,
                'maks_percobaan' => $request->has('maks_percobaan') ? $request->maks_percobaan : 3,
                'acak_soal' => $request->has('acak_soal') ? $request->acak_soal : true,
                'tampilkan_kunci_setelah' => $request->has('tampilkan_kunci_setelah') ? $request->tampilkan_kunci_setelah : true,
                'durasi_menit' => $request->durasi_menit,
            ]);

            if ($request->has('soal') && is_array($request->soal)) {
                foreach ($request->soal as $item) {
                    \App\Models\SoalPostTest::create([
                        'post_test_id' => $postTest->post_test_id,
                        'teks_soal' => $item['teks_soal'],
                        'pilihan_jawaban_json' => json_encode($item['pilihan_jawaban_json']),
                        'kunci_jawaban' => $item['kunci_jawaban'],
                        'bobot_nilai' => $item['bobot_nilai'] ?? 1,
                    ]);
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'message' => 'Post Test dan soal berhasil ditambahkan',
                'data' => $postTest->load('soalPostTest')
            ], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['message' => 'Gagal menyimpan post test: ' . $e->getMessage()], 500);
        }
    }

    public function show(Request $request, $id)
    {
        $postTest = \App\Models\PostTest::with('soalPostTest')->findOrFail($id);

        if (!$this->isPembelajaranAdmin($request->user(), $postTest->pembelajaran_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        return response()->json([
            'message' => 'Detail Post Test berhasil diambil',
            'data' => $postTest
        ]);
    }

    public function update(Request $request, $id)
    {
        $postTest = \App\Models\PostTest::findOrFail($id);

        if (!$this->isPembelajaranAdmin($request->user(), $postTest->pembelajaran_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $pembelajaran = \App\Models\Pembelajaran::find($postTest->pembelajaran_id);
        if ($pembelajaran->status === 'dipublikasikan') {
            return response()->json(['message' => 'Tidak dapat mengubah post test pada pembelajaran yang sudah dipublikasikan.'], 400);
        }

        $request->validate([
            'nilai_kelulusan' => 'sometimes|numeric|min:0|max:100',
            'maks_percobaan' => 'sometimes|integer|min:1',
            'acak_soal' => 'sometimes|boolean',
            'tampilkan_kunci_setelah' => 'sometimes|boolean',
            'durasi_menit' => 'nullable|integer|min:1',
            'soal' => 'nullable|array',
            'soal.*.teks_soal' => 'required|string',
            'soal.*.pilihan_jawaban_json' => 'required|array',
            'soal.*.kunci_jawaban' => 'required|string|max:10',
            'soal.*.bobot_nilai' => 'nullable|numeric|min:0',
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $postTest->update($request->only([
                'nilai_kelulusan', 'maks_percobaan', 'acak_soal', 'tampilkan_kunci_setelah', 'durasi_menit'
            ]));

            if ($request->has('soal') && is_array($request->soal)) {
                \App\Models\SoalPostTest::where('post_test_id', $postTest->post_test_id)->delete();

                foreach ($request->soal as $item) {
                    \App\Models\SoalPostTest::create([
                        'post_test_id' => $postTest->post_test_id,
                        'teks_soal' => $item['teks_soal'],
                        'pilihan_jawaban_json' => json_encode($item['pilihan_jawaban_json']),
                        'kunci_jawaban' => $item['kunci_jawaban'],
                        'bobot_nilai' => $item['bobot_nilai'] ?? 1,
                    ]);
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'message' => 'Post Test berhasil diperbarui',
                'data' => $postTest->fresh('soalPostTest')
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['message' => 'Gagal memperbarui post test: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $postTest = \App\Models\PostTest::findOrFail($id);

        if (!$this->isPembelajaranAdmin($request->user(), $postTest->pembelajaran_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $pembelajaran = \App\Models\Pembelajaran::find($postTest->pembelajaran_id);
        if ($pembelajaran->status === 'dipublikasikan') {
            return response()->json(['message' => 'Tidak dapat menghapus post test pada pembelajaran yang sudah dipublikasikan.'], 400);
        }

        $postTest->delete();

        return response()->json([
            'message' => 'Post Test berhasil dihapus'
        ]);
    }

}
