<?php

namespace App\Http\Controllers\Api\AdminKomunitas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MateriController extends Controller
{
    private function isPembelajaranAdmin($user, $pembelajaran_id) {
        $pembelajaran = \App\Models\Pembelajaran::find($pembelajaran_id);
        if (!$pembelajaran) return false;

        return \App\Models\AdminKomunitas::where('pengguna_id', $user->pengguna_id)
                    ->where('komunitas_id', $pembelajaran->komunitas_id)
                    ->exists();
    }

    private function rekalkulasiDurasiModul($modul_id) {
        $modul = \App\Models\Modul::find($modul_id);
        if (!$modul) return;

        $totalMenit = \App\Models\Materi::where('modul_id', $modul_id)->sum('durasi_menit');
        $jp = min(3, $totalMenit / 135);

        $modul->update([
            'durasi_total_menit' => $totalMenit,
            'jp_modul' => $jp
        ]);
    }

    public function index(Request $request, $modul_id)
    {
        $modul = \App\Models\Modul::findOrFail($modul_id);
        
        if (!$this->isPembelajaranAdmin($request->user(), $modul->pembelajaran_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $materi = \App\Models\Materi::where('modul_id', $modul_id)
                    ->orderBy('urutan', 'asc')
                    ->get();

        return response()->json([
            'message' => 'Daftar Materi berhasil diambil',
            'data' => $materi
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
            return response()->json(['message' => 'Tidak dapat menambah materi ke pembelajaran yang sudah dipublikasikan.'], 400);
        }

        $request->validate([
            'judul_materi' => 'required|string|max:255',
            'tipe_materi' => 'required|in:pdf,video_embed',
            'tautan_atau_berkas_embed' => 'required_if:tipe_materi,video_embed|string',
            'file_pdf' => 'required_if:tipe_materi,pdf|file|mimes:pdf|max:10240',
            'durasi_menit' => 'required|integer|min:1',
            'apakah_wajib' => 'nullable|in:0,1,true,false',
            'urutan' => 'required|integer|min:1',
        ]);

        $url = '';
        if ($request->tipe_materi === 'pdf') {
            $path = $request->file('file_pdf')->store('materi_pdf', 'public');
            $url = '/storage/' . $path;
        } else {
            $url = $request->tautan_atau_berkas_embed;
        }

        $materi = \App\Models\Materi::create([
            'modul_id' => $modul_id,
            'judul_materi' => $request->judul_materi,
            'tipe_materi' => $request->tipe_materi,
            'tautan_atau_berkas' => $url,
            'durasi_menit' => $request->durasi_menit,
            'apakah_wajib' => $request->has('apakah_wajib') ? filter_var($request->apakah_wajib, FILTER_VALIDATE_BOOLEAN) : true,
            'urutan' => $request->urutan,
        ]);

        $this->rekalkulasiDurasiModul($modul_id);

        return response()->json([
            'message' => 'Materi berhasil ditambahkan',
            'data' => $materi
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $materi = \App\Models\Materi::findOrFail($id);
        $modul = \App\Models\Modul::find($materi->modul_id);

        if (!$this->isPembelajaranAdmin($request->user(), $modul->pembelajaran_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        return response()->json([
            'message' => 'Detail Materi berhasil diambil',
            'data' => $materi
        ]);
    }

    public function update(Request $request, $id)
    {
        $materi = \App\Models\Materi::findOrFail($id);
        $modul = \App\Models\Modul::find($materi->modul_id);

        if (!$this->isPembelajaranAdmin($request->user(), $modul->pembelajaran_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $pembelajaran = \App\Models\Pembelajaran::find($modul->pembelajaran_id);
        if ($pembelajaran->status === 'dipublikasikan') {
            return response()->json(['message' => 'Tidak dapat mengubah materi pada pembelajaran yang sudah dipublikasikan.'], 400);
        }

        $request->validate([
            'judul_materi' => 'sometimes|string|max:255',
            'durasi_menit' => 'sometimes|integer|min:1',
            'apakah_wajib' => 'nullable|in:0,1,true,false',
            'urutan' => 'sometimes|integer|min:1',
        ]);

        $updateData = $request->only(['judul_materi', 'durasi_menit', 'urutan']);
        if ($request->has('apakah_wajib')) {
            $updateData['apakah_wajib'] = filter_var($request->apakah_wajib, FILTER_VALIDATE_BOOLEAN);
        }
        $materi->update($updateData);

        $this->rekalkulasiDurasiModul($modul->modul_id);

        return response()->json([
            'message' => 'Materi berhasil diperbarui',
            'data' => $materi
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $materi = \App\Models\Materi::findOrFail($id);
        $modul = \App\Models\Modul::find($materi->modul_id);

        if (!$this->isPembelajaranAdmin($request->user(), $modul->pembelajaran_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $pembelajaran = \App\Models\Pembelajaran::find($modul->pembelajaran_id);
        if ($pembelajaran->status === 'dipublikasikan') {
            return response()->json(['message' => 'Tidak dapat menghapus materi pada pembelajaran yang sudah dipublikasikan.'], 400);
        }

        $materi->delete();
        
        $this->rekalkulasiDurasiModul($modul->modul_id);

        return response()->json([
            'message' => 'Materi berhasil dihapus'
        ]);
    }

}
