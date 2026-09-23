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
        // Pertahankan nilai jp_modul jika sudah diisi manual (> 0), jika 0 baru gunakan estimasi default
        $jp = ($modul->jp_modul && (float)$modul->jp_modul > 0)
            ? (float)$modul->jp_modul
            : round(min(3, $totalMenit / 135), 2);

        $modul->update([
            'durasi_total_menit' => $totalMenit,
            'jp_modul' => $jp
        ]);

        // Sinkronisasi PembelajaranJp jika rekornya ada
        $pembelajaranId = $modul->pembelajaran_id;
        $totalDurasiPembelajaran = \App\Models\Modul::where('pembelajaran_id', $pembelajaranId)->sum('durasi_total_menit');
        $totalJpPembelajaran = \App\Models\Modul::where('pembelajaran_id', $pembelajaranId)->sum('jp_modul');

        \App\Models\PembelajaranJp::updateOrCreate(
            ['pembelajaran_id' => $pembelajaranId],
            [
                'durasi_menit' => $totalDurasiPembelajaran,
                'jp_dihitung_sistem' => $totalJpPembelajaran,
            ]
        );
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
            $pembelajaran->update(['status' => 'draft']);
        }

        $request->validate([
            'judul_materi' => 'required|string|max:255',
            'tipe_materi' => 'required|in:pdf,video_embed',
            'tautan_atau_berkas_embed' => 'required_if:tipe_materi,video_embed|nullable|url|max:500',
            'file_pdf' => 'required_if:tipe_materi,pdf|nullable|file|mimes:pdf|max:10240',
            'durasi_menit' => 'nullable|integer|min:1',
            'apakah_wajib' => 'nullable',
            'urutan' => [
                'nullable', 'integer', 'min:1',
                \Illuminate\Validation\Rule::unique('materi')->where('modul_id', $modul_id)
            ],
        ]);

        $url = '';
        if ($request->tipe_materi === 'pdf') {
            if (!$request->hasFile('file_pdf')) {
                return response()->json(['message' => 'File PDF wajib diunggah.'], 422);
            }
            $path = $request->file('file_pdf')->store('materi_pdf', 'public');
            $url = '/storage/' . $path;
        } else {
            $url = $request->tautan_atau_berkas_embed ?: '';
        }

        $maxUrutan = \App\Models\Materi::where('modul_id', $modul_id)->max('urutan') ?? 0;
        $urutan = $request->urutan ?? ($maxUrutan + 1);
        $durasiMenit = $request->durasi_menit ?? 15;

        $materi = \App\Models\Materi::create([
            'modul_id' => $modul_id,
            'judul_materi' => $request->judul_materi,
            'tipe_materi' => $request->tipe_materi,
            'tautan_atau_berkas' => $url,
            'durasi_menit' => $durasiMenit,
            'apakah_wajib' => $request->has('apakah_wajib') ? filter_var($request->apakah_wajib, FILTER_VALIDATE_BOOLEAN) : true,
            'urutan' => $urutan,
        ]);

        $this->rekalkulasiDurasiModul($modul_id);
        \App\Services\CourseProgressService::syncCourseParticipants($modul->pembelajaran_id);

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
            $pembelajaran->update(['status' => 'draft']);
        }

        $request->validate([
            'judul_materi' => 'sometimes|string|max:255',
            'durasi_menit' => 'sometimes|integer|min:1',
            'apakah_wajib' => 'nullable|in:0,1,true,false',
            'urutan' => [
                'sometimes', 'integer', 'min:1',
                \Illuminate\Validation\Rule::unique('materi')->where('modul_id', $modul->modul_id)->ignore($id, 'materi_id')
            ],
        ]);

        $updateData = $request->only(['judul_materi', 'durasi_menit', 'urutan']);
        if ($request->has('apakah_wajib')) {
            $updateData['apakah_wajib'] = filter_var($request->apakah_wajib, FILTER_VALIDATE_BOOLEAN);
        }
        $materi->update($updateData);

        $this->rekalkulasiDurasiModul($modul->modul_id);
        \App\Services\CourseProgressService::syncCourseParticipants($modul->pembelajaran_id);

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
        if ($pembelajaran && $pembelajaran->status === 'dipublikasikan') {
            $pembelajaran->update(['status' => 'draft']);
        }

        // Hapus berkas fisik jika bertipe PDF
        if ($materi->tipe_materi === 'pdf' && $materi->tautan_atau_berkas) {
            $filePath = str_replace('/storage/', '', $materi->tautan_atau_berkas);
            \Illuminate\Support\Facades\Storage::disk('public')->delete($filePath);
        }

        $pembelajaranId = $modul->pembelajaran_id;
        $materi->delete();
        
        $this->rekalkulasiDurasiModul($modul->modul_id);
        \App\Services\CourseProgressService::syncCourseParticipants($pembelajaranId);

        return response()->json([
            'message' => 'Materi berhasil dihapus'
        ]);
    }

}
