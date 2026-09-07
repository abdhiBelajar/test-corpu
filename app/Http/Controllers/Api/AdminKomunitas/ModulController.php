<?php

namespace App\Http\Controllers\Api\AdminKomunitas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ModulController extends Controller
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

        $moduls = \App\Models\Modul::where('pembelajaran_id', $pembelajaran_id)
                    ->orderBy('urutan', 'asc')
                    ->with('materi', 'kuis')
                    ->get();

        return response()->json([
            'message' => 'Daftar Modul berhasil diambil',
            'data' => $moduls
        ]);
    }

    public function store(Request $request, $pembelajaran_id)
    {
        if (!$this->isPembelajaranAdmin($request->user(), $pembelajaran_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $pembelajaran = \App\Models\Pembelajaran::find($pembelajaran_id);
        if ($pembelajaran->status === 'dipublikasikan') {
            return response()->json(['message' => 'Tidak dapat menambah modul ke pembelajaran yang sudah dipublikasikan.'], 400);
        }

        $request->validate([
            'judul_modul' => 'required|string|max:255',
            'gambaran_umum' => 'required|string',
            'evaluasi_deskripsi' => 'nullable|string',
            'urutan' => 'required|integer|min:1',
            'info_tatap_muka' => 'nullable|string',
        ]);

        $modul = \App\Models\Modul::create([
            'pembelajaran_id' => $pembelajaran_id,
            'judul_modul' => $request->judul_modul,
            'gambaran_umum' => $request->gambaran_umum,
            'evaluasi_deskripsi' => $request->evaluasi_deskripsi,
            'urutan' => $request->urutan,
            'durasi_total_menit' => 0,
            'jp_modul' => 0,
            'info_tatap_muka' => $request->info_tatap_muka,
        ]);

        return response()->json([
            'message' => 'Modul berhasil ditambahkan',
            'data' => $modul
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $modul = \App\Models\Modul::with('materi', 'kuis')->findOrFail($id);

        if (!$this->isPembelajaranAdmin($request->user(), $modul->pembelajaran_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        return response()->json([
            'message' => 'Detail Modul berhasil diambil',
            'data' => $modul
        ]);
    }

    public function update(Request $request, $id)
    {
        $modul = \App\Models\Modul::findOrFail($id);

        if (!$this->isPembelajaranAdmin($request->user(), $modul->pembelajaran_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $pembelajaran = \App\Models\Pembelajaran::find($modul->pembelajaran_id);
        if ($pembelajaran->status === 'dipublikasikan') {
            return response()->json(['message' => 'Tidak dapat mengubah modul pada pembelajaran yang sudah dipublikasikan.'], 400);
        }

        $request->validate([
            'judul_modul' => 'sometimes|string|max:255',
            'gambaran_umum' => 'sometimes|string',
            'evaluasi_deskripsi' => 'nullable|string',
            'urutan' => 'sometimes|integer|min:1',
            'info_tatap_muka' => 'nullable|string',
        ]);

        $modul->update($request->only([
            'judul_modul', 'gambaran_umum', 'evaluasi_deskripsi', 'urutan', 'info_tatap_muka'
        ]));

        return response()->json([
            'message' => 'Modul berhasil diperbarui',
            'data' => $modul
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $modul = \App\Models\Modul::findOrFail($id);

        if (!$this->isPembelajaranAdmin($request->user(), $modul->pembelajaran_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $pembelajaran = \App\Models\Pembelajaran::find($modul->pembelajaran_id);
        if ($pembelajaran->status === 'dipublikasikan') {
            return response()->json(['message' => 'Tidak dapat menghapus modul pada pembelajaran yang sudah dipublikasikan.'], 400);
        }

        $modul->delete();

        return response()->json([
            'message' => 'Modul berhasil dihapus'
        ]);
    }

}
