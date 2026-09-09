<?php

namespace App\Http\Controllers\Api\AdminKomunitas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PembelajaranController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Dapatkan semua ID komunitas di mana user adalah admin
        $komunitasIds = \App\Models\AdminKomunitas::where('pengguna_id', $user->pengguna_id)
                            ->pluck('komunitas_id');
                            
        $pembelajaran = \App\Models\Pembelajaran::whereIn('komunitas_id', $komunitasIds)
                            ->with('komunitas')
                            ->get();
                            
        return response()->json([
            'message' => 'Daftar Pembelajaran berhasil diambil',
            'data' => $pembelajaran
        ]);
    }

    public function myKomunitas(Request $request)
    {
        $user = $request->user();
        $komunitas = \App\Models\AdminKomunitas::where('pengguna_id', $user->pengguna_id)
            ->with('komunitas')
            ->get()
            ->pluck('komunitas');
            
        return response()->json([
            'message' => 'Komunitas berhasil diambil',
            'data' => $komunitas
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'komunitas_id' => 'required|exists:komunitas,komunitas_id',
            'judul_pembelajaran' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'kategori' => 'nullable|string|max:100',
            'capaian_pembelajaran' => 'required|string',
            'nama_narasumber' => 'nullable|string|max:100',
            'nilai_kelulusan' => 'required|numeric|min:0|max:100',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
        ]);

        $user = $request->user();

        // Verifikasi bahwa user adalah admin dari komunitas ini
        $isAdmin = \App\Models\AdminKomunitas::where('pengguna_id', $user->pengguna_id)
                        ->where('komunitas_id', $request->komunitas_id)
                        ->exists();

        if (!$isAdmin) {
            return response()->json([
                'message' => 'Anda bukan admin untuk komunitas ini.'
            ], 403);
        }

        $pembelajaran = \App\Models\Pembelajaran::create([
            'komunitas_id' => $request->komunitas_id,
            'dirancang_oleh_pengguna_id' => $user->pengguna_id,
            'judul_pembelajaran' => $request->judul_pembelajaran,
            'deskripsi' => $request->deskripsi,
            'kategori' => $request->kategori,
            'capaian_pembelajaran' => $request->capaian_pembelajaran,
            'nama_narasumber' => $request->nama_narasumber,
            'nilai_kelulusan' => $request->nilai_kelulusan,
            'ringkasan_materi' => $request->ringkasan_materi ?? '-', // Default value, will be updated during approval
            'status' => 'draft',
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
        ]);

        return response()->json([
            'message' => 'Draf pembelajaran berhasil dibuat',
            'data' => $pembelajaran
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $user = $request->user();
        $pembelajaran = \App\Models\Pembelajaran::with(['komunitas', 'pembelajaranJp'])->findOrFail($id);

        // Verifikasi bahwa user adalah admin dari komunitas ini
        $isAdmin = \App\Models\AdminKomunitas::where('pengguna_id', $user->pengguna_id)
                        ->where('komunitas_id', $pembelajaran->komunitas_id)
                        ->exists();

        if (!$isAdmin) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        return response()->json([
            'message' => 'Detail pembelajaran berhasil diambil',
            'data' => $pembelajaran
        ]);
    }

    public function update(Request $request, string $id)
    {
        $user = $request->user();
        $pembelajaran = \App\Models\Pembelajaran::findOrFail($id);

        $isAdmin = \App\Models\AdminKomunitas::where('pengguna_id', $user->pengguna_id)
                        ->where('komunitas_id', $pembelajaran->komunitas_id)
                        ->exists();

        if (!$isAdmin) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        if ($pembelajaran->status === 'dipublikasikan') {
            return response()->json(['message' => 'Pembelajaran yang sudah dipublikasikan tidak dapat diubah.'], 400);
        }

        $request->validate([
            'judul_pembelajaran' => 'sometimes|string|max:255',
            'deskripsi' => 'nullable|string',
            'kategori' => 'nullable|string|max:100',
            'capaian_pembelajaran' => 'sometimes|string',
            'nama_narasumber' => 'nullable|string|max:100',
            'nilai_kelulusan' => 'sometimes|numeric|min:0|max:100',
            'ringkasan_materi' => 'nullable|string',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
        ]);

        $pembelajaran->update($request->only([
            'judul_pembelajaran', 'deskripsi', 'kategori', 'capaian_pembelajaran',
            'nama_narasumber', 'nilai_kelulusan', 'ringkasan_materi', 'tanggal_mulai', 'tanggal_selesai'
        ]));

        return response()->json([
            'message' => 'Pembelajaran berhasil diperbarui',
            'data' => $pembelajaran
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        $pembelajaran = \App\Models\Pembelajaran::findOrFail($id);

        $isAdmin = \App\Models\AdminKomunitas::where('pengguna_id', $user->pengguna_id)
                        ->where('komunitas_id', $pembelajaran->komunitas_id)
                        ->exists();

        if (!$isAdmin) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        if ($pembelajaran->status !== 'draft') {
            return response()->json(['message' => 'Hanya pembelajaran berstatus draft yang dapat dihapus.'], 400);
        }

        $pembelajaran->delete();

        return response()->json([
            'message' => 'Pembelajaran berhasil dihapus'
        ]);
    }

    public function ajukanApproval(Request $request, string $id)
    {
        $user = $request->user();
        $pembelajaran = \App\Models\Pembelajaran::findOrFail($id);

        $isAdmin = \App\Models\AdminKomunitas::where('pengguna_id', $user->pengguna_id)
                        ->where('komunitas_id', $pembelajaran->komunitas_id)
                        ->exists();

        if (!$isAdmin) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        if ($pembelajaran->status !== 'draft' && $pembelajaran->status !== 'menunggu_approval') {
            return response()->json(['message' => 'Pembelajaran ini tidak dapat diajukan (status saat ini: '.$pembelajaran->status.').'], 400);
        }

        $request->validate([
            'ringkasan_materi' => 'required|string',
            'surat_pernyataan' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048', // Opsional jika sudah pernah upload
        ]);

        $updateData = [
            'ringkasan_materi' => $request->ringkasan_materi,
            'status' => 'menunggu_approval'
        ];

        if ($request->hasFile('surat_pernyataan')) {
            $path = $request->file('surat_pernyataan')->store('surat_pernyataan', 'public');
            $updateData['surat_pernyataan_url'] = '/storage/' . $path;
        }

        // Cek validasi 3 unsur wajib (Overview, Substansi, Evaluasi)
        $moduls = \App\Models\Modul::where('pembelajaran_id', $id)->get();
        if ($moduls->isEmpty()) {
            return response()->json(['message' => 'Pembelajaran harus memiliki minimal 1 modul sebelum diajukan.'], 400);
        }

        foreach ($moduls as $modul) {
            $hasMateri = \App\Models\Materi::where('modul_id', $modul->modul_id)->exists();
            $hasKuis = \App\Models\Kuis::where('modul_id', $modul->modul_id)->exists();
            
            if (empty($modul->gambaran_umum) || !$hasMateri || !$hasKuis || empty($modul->evaluasi_deskripsi)) {
                return response()->json([
                    'message' => "Modul '{$modul->judul_modul}' belum lengkap unsur wajibnya (Overview, Substansi, Evaluasi)."
                ], 400);
            }
        }

        $pembelajaran->update($updateData);

        // Tambah/Update ke tabel ValidasiPembelajaran
        \App\Models\ValidasiPembelajaran::updateOrCreate(
            ['pembelajaran_id' => $pembelajaran->pembelajaran_id],
            [
                'divalidasi_oleh_pengguna_id' => $user->pengguna_id, // Default ke diri sendiri sementara menunggu direview Admin BKPSDM
                'status_validasi' => 'diajukan',
                'catatan' => null
            ]
        );

        return response()->json([
            'message' => 'Pembelajaran berhasil diajukan untuk approval',
            'data' => $pembelajaran
        ]);
    }

}