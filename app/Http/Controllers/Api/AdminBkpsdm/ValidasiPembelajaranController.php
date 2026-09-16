<?php

namespace App\Http\Controllers\Api\AdminBkpsdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ValidasiPembelajaranController extends Controller
{
    public function index()
    {
        $pembelajaran = \App\Models\Pembelajaran::where('status', 'menunggu_approval')
            ->with(['perancang', 'komunitas', 'pembelajaranJp'])
            ->latest('pembelajaran_id')
            ->get();

        return response()->json([
            'message' => 'Daftar Pembelajaran Menunggu Approval',
            'data' => $pembelajaran
        ]);
    }

    public function store(\Illuminate\Http\Request $request, string $pembelajaran_id)
    {
        $request->validate([
            'status_validasi' => 'required|in:disetujui,ditolak',
            'catatan' => 'nullable|string',
            'jp_final' => 'nullable|numeric|min:0|max:100'
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $pembelajaran_id) {
            $pembelajaran = \App\Models\Pembelajaran::where('status', 'menunggu_approval')
                ->lockForUpdate()
                ->findOrFail($pembelajaran_id);

            // Validasi & Verifikasi JP jika disetujui (sesuai SOP BKPSDM)
            if ($request->status_validasi === 'disetujui') {
                $jp = \App\Models\PembelajaranJp::where('pembelajaran_id', $pembelajaran->pembelajaran_id)->first();
                
                $finalJp = $request->filled('jp_final') 
                    ? (float) $request->jp_final 
                    : ($jp ? ($jp->jp_final ?: ($jp->jp_dihitung_sistem ?: 0)) : 0);

                if ($jp) {
                    $jp->update([
                        'jp_final' => $finalJp,
                        'diverifikasi_oleh_bkpsdm' => true,
                        'diverifikasi_pada' => now()
                    ]);
                } else {
                    \App\Models\PembelajaranJp::create([
                        'pembelajaran_id' => $pembelajaran->pembelajaran_id,
                        'jenis_pelatihan' => 'formal',
                        'durasi_menit' => 0,
                        'jp_dihitung_sistem' => $finalJp,
                        'jp_final' => $finalJp,
                        'diverifikasi_oleh_bkpsdm' => true,
                        'diverifikasi_pada' => now()
                    ]);
                }
            }

            // Rekam Validasi
            $validasi = \App\Models\ValidasiPembelajaran::create([
                'pembelajaran_id' => $pembelajaran->pembelajaran_id,
                'divalidasi_oleh_pengguna_id' => $request->user()->pengguna_id,
                'status_validasi' => $request->status_validasi,
                'catatan' => $request->catatan,
                'divalidasi_pada' => now()
            ]);

            // Update status Pembelajaran
            if ($request->status_validasi === 'disetujui') {
                $pembelajaran->update([
                    'status' => 'dipublikasikan',
                    'dipublikasikan_pada' => now()
                ]);
            } else {
                $pembelajaran->update([
                    'status' => 'ditolak'
                ]);
            }

            return response()->json([
                'message' => 'Validasi berhasil disimpan',
                'data' => [
                    'pembelajaran' => $pembelajaran,
                    'validasi' => $validasi
                ]
            ]);
        });
    }

    public function show(string $id)
    {
        $pembelajaran = \App\Models\Pembelajaran::with([
            'pembelajaranJp',
            'moduls.materis',
            'moduls.kuis',
            'postTests'
        ])->findOrFail($id);

        return response()->json([
            'message' => 'Detail pembelajaran berhasil diambil',
            'data' => $pembelajaran
        ]);
    }
}
