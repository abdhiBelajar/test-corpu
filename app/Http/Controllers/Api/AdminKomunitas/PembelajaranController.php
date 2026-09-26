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
                            ->with(['komunitas', 'pembelajaranJp', 'modul.materi', 'validasi', 'kategoriKursus'])
                            ->get()
                            ->map(function ($c) {
                                $totalModul = $c->modul ? $c->modul->count() : 0;
                                $calculatedJp = $c->pembelajaranJp && $c->pembelajaranJp->isNotEmpty()
                                    ? ($c->pembelajaranJp->first()->jp_final ?? $c->pembelajaranJp->first()->jp_dihitung_sistem)
                                    : round($c->modul ? $c->modul->sum('jp_modul') : 0, 1);

                                $pendaftaran = \App\Models\PendaftaranPembelajaran::where('pembelajaran_id', $c->pembelajaran_id)->get();
                                $totalPeserta = $pendaftaran->count();
                                $avgProg = $totalPeserta > 0 ? round($pendaftaran->avg('persentase_progres') ?? 0, 1) : 0;

                                $c->modules_count = $totalModul;
                                $c->modules = $totalModul;
                                $c->jpl = $calculatedJp > 0 ? $calculatedJp : 2;
                                $c->peserta_count = $totalPeserta;
                                $c->total_peserta = $totalPeserta;
                                $c->avg_progres = $avgProg;
                                return $c;
                            });
                            
        return response()->json([
            'message' => 'Daftar Pembelajaran berhasil diambil',
            'data' => $pembelajaran
        ]);
    }

    public function myKomunitas(Request $request)
    {
        $user = $request->user();
        $komunitasIds = \App\Models\AdminKomunitas::where('pengguna_id', $user->pengguna_id)->pluck('komunitas_id');
        $komunitas = \App\Models\Komunitas::whereIn('komunitas_id', $komunitasIds)->get();
            
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
            'kategori_id' => 'nullable|exists:kategori_kursus,kategori_id',
            'kategori' => 'nullable|string|max:100',
            'capaian_pembelajaran' => 'required|string',
            'nama_narasumber' => 'nullable|string|max:100',
            'nilai_kelulusan' => 'required|numeric|min:0|max:100',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'thumbnail.max' => 'Ukuran thumbnail tidak boleh lebih dari 2MB.',
            'thumbnail.image' => 'File thumbnail harus berupa gambar.',
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

        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('thumbnails/pembelajaran', 'public');
        }

        $kategoriId = $request->kategori_id;
        $kategoriNama = $request->kategori;

        if ($kategoriId) {
            $katObj = \App\Models\KategoriKursus::find($kategoriId);
            if ($katObj) {
                $kategoriNama = $katObj->nama_kategori;
            }
        } elseif ($kategoriNama) {
            $katObj = \App\Models\KategoriKursus::where('nama_kategori', $kategoriNama)->first();
            if ($katObj) {
                $kategoriId = $katObj->kategori_id;
            }
        }

        $pembelajaran = \App\Models\Pembelajaran::create([
            'komunitas_id' => $request->komunitas_id,
            'dirancang_oleh_pengguna_id' => $user->pengguna_id,
            'judul_pembelajaran' => $request->judul_pembelajaran,
            'deskripsi' => $request->deskripsi,
            'kategori_id' => $kategoriId,
            'kategori' => $kategoriNama ?: 'Pengembangan Kompetensi',
            'capaian_pembelajaran' => $request->capaian_pembelajaran,
            'nama_narasumber' => $request->nama_narasumber,
            'nilai_kelulusan' => $request->nilai_kelulusan,
            'ringkasan_materi' => $request->ringkasan_materi ?? '-', // Default value, will be updated during approval
            'status' => 'draft',
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'thumbnail' => $thumbnailPath,
        ]);

        return response()->json([
            'message' => 'Draf pembelajaran berhasil dibuat',
            'data' => $pembelajaran
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $user = $request->user();
        $pembelajaran = \App\Models\Pembelajaran::with(['komunitas', 'pembelajaranJp', 'validasi.pemvalidasi', 'modul.materi', 'kategoriKursus'])->findOrFail($id);

        // Verifikasi bahwa user adalah admin dari komunitas ini
        $isAdmin = \App\Models\AdminKomunitas::where('pengguna_id', $user->pengguna_id)
                        ->where('komunitas_id', $pembelajaran->komunitas_id)
                        ->exists();

        if (!$isAdmin) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $calculatedJp = $pembelajaran->pembelajaranJp && $pembelajaran->pembelajaranJp->isNotEmpty()
            ? ($pembelajaran->pembelajaranJp->first()->jp_final ?? $pembelajaran->pembelajaranJp->first()->jp_dihitung_sistem)
            : round($pembelajaran->modul ? $pembelajaran->modul->sum('jp_modul') : 0, 1);
        $pembelajaran->jpl = $calculatedJp > 0 ? $calculatedJp : 2;

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

        $request->validate([
            'judul_pembelajaran' => 'sometimes|string|max:255',
            'deskripsi' => 'nullable|string',
            'kategori_id' => 'nullable|exists:kategori_kursus,kategori_id',
            'kategori' => 'nullable|string|max:100',
            'capaian_pembelajaran' => 'sometimes|string',
            'nama_narasumber' => 'nullable|string|max:100',
            'nilai_kelulusan' => 'sometimes|numeric|min:0|max:100',
            'ringkasan_materi' => 'nullable|string',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'surat_pernyataan' => 'nullable|file|mimes:pdf|max:5120',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'thumbnail.max' => 'Ukuran thumbnail tidak boleh lebih dari 2MB.',
            'thumbnail.image' => 'File thumbnail harus berupa gambar.',
        ]);

        $updateData = $request->only([
            'judul_pembelajaran', 'deskripsi', 'kategori_id', 'kategori', 'capaian_pembelajaran',
            'nama_narasumber', 'nilai_kelulusan', 'ringkasan_materi', 'tanggal_mulai', 'tanggal_selesai'
        ]);

        if ($request->has('kategori_id')) {
            $updateData['kategori_id'] = $request->kategori_id;
            if ($request->kategori_id) {
                $katObj = \App\Models\KategoriKursus::find($request->kategori_id);
                if ($katObj) {
                    $updateData['kategori'] = $katObj->nama_kategori;
                }
            }
        } elseif ($request->has('kategori')) {
            $updateData['kategori'] = $request->kategori ?: 'Pengembangan Kompetensi';
            $katObj = \App\Models\KategoriKursus::where('nama_kategori', $updateData['kategori'])->first();
            if ($katObj) {
                $updateData['kategori_id'] = $katObj->kategori_id;
            }
        }

        // Jika kursus berstatus dipublikasikan atau ditolak, saat diedit otomatis kembali menjadi menunggu_approval
        if ($pembelajaran->status === 'dipublikasikan' || $pembelajaran->status === 'ditolak') {
            $updateData['status'] = 'menunggu_approval';

            \App\Models\ValidasiPembelajaran::updateOrCreate(
                ['pembelajaran_id' => $pembelajaran->pembelajaran_id],
                [
                    'divalidasi_oleh_pengguna_id' => $user->pengguna_id,
                    'status_validasi' => 'diajukan',
                    'catatan' => null
                ]
            );
        }

        if ($request->hasFile('surat_pernyataan')) {
            $path = $request->file('surat_pernyataan')->store('surat_pernyataan', 'public');
            $updateData['surat_pernyataan_url'] = '/storage/' . $path;
        }

        if ($request->hasFile('thumbnail')) {
            if ($pembelajaran->thumbnail && \Illuminate\Support\Facades\Storage::disk('public')->exists($pembelajaran->thumbnail)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($pembelajaran->thumbnail);
            }
            $updateData['thumbnail'] = $request->file('thumbnail')->store('thumbnails/pembelajaran', 'public');
        }

        $pembelajaran->update($updateData);

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

        \Illuminate\Support\Facades\DB::transaction(function () use ($pembelajaran) {
            // Hapus file thumbnail jika ada
            if ($pembelajaran->thumbnail && \Illuminate\Support\Facades\Storage::disk('public')->exists($pembelajaran->thumbnail)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($pembelajaran->thumbnail);
            }

            // Hapus file surat pernyataan jika ada
            if ($pembelajaran->surat_pernyataan_url) {
                $filePath = str_replace('/storage/', '', $pembelajaran->surat_pernyataan_url);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($filePath);
            }

            // Hapus file materi PDF jika ada
            $modulIds = \App\Models\Modul::where('pembelajaran_id', $pembelajaran->pembelajaran_id)->pluck('modul_id');
            $materis = \App\Models\Materi::whereIn('modul_id', $modulIds)->where('tipe_materi', 'pdf')->get();
            foreach ($materis as $materi) {
                if ($materi->tautan_atau_berkas) {
                    $materiFilePath = str_replace('/storage/', '', $materi->tautan_atau_berkas);
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($materiFilePath);
                }
            }

            $pembelajaran->delete();
        });

        return response()->json([
            'message' => 'Pembelajaran berhasil dihapus'
        ]);
    }

    public function hapusSuratPernyataan(Request $request, string $id)
    {
        $user = $request->user();
        $pembelajaran = \App\Models\Pembelajaran::findOrFail($id);

        $isAdmin = \App\Models\AdminKomunitas::where('pengguna_id', $user->pengguna_id)
                        ->where('komunitas_id', $pembelajaran->komunitas_id)
                        ->exists();

        if (!$isAdmin) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        if ($pembelajaran->surat_pernyataan_url) {
            $filePath = str_replace('/storage/', '', $pembelajaran->surat_pernyataan_url);
            \Illuminate\Support\Facades\Storage::disk('public')->delete($filePath);
            $pembelajaran->update(['surat_pernyataan_url' => null]);
        }

        return response()->json([
            'message' => 'Surat pernyataan keabsahan berhasil dihapus.',
            'data' => $pembelajaran
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

        if ($pembelajaran->status !== 'draft' && $pembelajaran->status !== 'menunggu_approval' && $pembelajaran->status !== 'ditolak') {
            return response()->json(['message' => 'Pembelajaran ini tidak dapat diajukan (status saat ini: '.$pembelajaran->status.').'], 400);
        }

        $request->validate([
            'ringkasan_materi' => 'nullable|string',
            'surat_pernyataan' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        // Cek validasi 3 unsur wajib (Overview, Substansi, Evaluasi dengan soal)
        $moduls = \App\Models\Modul::where('pembelajaran_id', $id)->get();
        if ($moduls->isEmpty()) {
            return response()->json(['message' => 'Pembelajaran harus memiliki minimal 1 modul sebelum diajukan.'], 400);
        }

        foreach ($moduls as $modul) {
            $hasMateri = \App\Models\Materi::where('modul_id', $modul->modul_id)->exists();
            $kuis = \App\Models\Kuis::where('modul_id', $modul->modul_id)->first();
            
            if (!$hasMateri) {
                return response()->json([
                    'message' => "Modul '{$modul->judul_modul}' belum memiliki materi. Silakan unggah minimal 1 materi sebelum mengajukan."
                ], 400);
            }
            if (!$kuis) {
                return response()->json([
                    'message' => "Modul '{$modul->judul_modul}' belum memiliki kuis evaluasi. Silakan buat kuis untuk modul ini sebelum mengajukan."
                ], 400);
            }
            $hasSoalKuis = \App\Models\SoalKuis::where('kuis_id', $kuis->kuis_id)->exists();
            if (!$hasSoalKuis) {
                return response()->json([
                    'message' => "Kuis pada modul '{$modul->judul_modul}' belum memiliki butir soal evaluasi."
                ], 400);
            }
        }

        // Cek apakah pembelajaran sudah memiliki Post-Test dan soal evaluasi
        $postTest = \App\Models\PostTest::where('pembelajaran_id', $id)->first();
        if (!$postTest) {
            return response()->json([
                'message' => 'Pembelajaran harus memiliki Post-Test sebelum diajukan.'
            ], 400);
        }

        $hasSoalPostTest = \App\Models\SoalPostTest::where('post_test_id', $postTest->post_test_id)->exists();
        if (!$hasSoalPostTest) {
            return response()->json([
                'message' => 'Post-Test pembelajaran belum memiliki soal evaluasi.'
            ], 400);
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $pembelajaran, $user) {
            $updateData = [
                'ringkasan_materi' => $request->ringkasan_materi ?: ($pembelajaran->ringkasan_materi ?: 'Ringkasan materi pembelajaran'),
                'status' => 'menunggu_approval'
            ];

            if (empty($pembelajaran->kategori)) {
                $updateData['kategori'] = 'Pengembangan Kompetensi';
            }

            if ($request->hasFile('surat_pernyataan')) {
                $path = $request->file('surat_pernyataan')->store('surat_pernyataan', 'public');
                $updateData['surat_pernyataan_url'] = '/storage/' . $path;
            }

            $pembelajaran->update($updateData);

            // Hitung dan sinkronisasi PembelajaranJp
            $totalDurasi = \App\Models\Modul::where('pembelajaran_id', $pembelajaran->pembelajaran_id)->sum('durasi_total_menit');
            $totalJp = \App\Models\Modul::where('pembelajaran_id', $pembelajaran->pembelajaran_id)->sum('jp_modul');

            \App\Models\PembelajaranJp::updateOrCreate(
                ['pembelajaran_id' => $pembelajaran->pembelajaran_id],
                [
                    'jenis_pelatihan'    => 'formal',
                    'durasi_menit'       => $totalDurasi,
                    'jp_dihitung_sistem' => $totalJp,
                ]
            );

            // Tambah/Update ke tabel ValidasiPembelajaran
            \App\Models\ValidasiPembelajaran::updateOrCreate(
                ['pembelajaran_id' => $pembelajaran->pembelajaran_id],
                [
                    'divalidasi_oleh_pengguna_id' => $user->pengguna_id,
                    'status_validasi' => 'diajukan',
                    'catatan' => null
                ]
            );

            return response()->json([
                'message' => 'Pembelajaran berhasil diajukan untuk approval',
                'data' => $pembelajaran->fresh(['komunitas', 'pembelajaranJp', 'validasi', 'modul.materi'])
            ]);
        });
    }

}