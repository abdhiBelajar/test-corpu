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
            'catatan' => 'nullable|string'
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $pembelajaran_id) {
            $pembelajaran = \App\Models\Pembelajaran::where('status', 'menunggu_approval')
                ->lockForUpdate()
                ->findOrFail($pembelajaran_id);

            // Validasi JP sebelum disetujui (sesuai SOP BKPSDM)
            if ($request->status_validasi === 'disetujui') {
                $jp = \App\Models\PembelajaranJp::where('pembelajaran_id', $pembelajaran->pembelajaran_id)->first();
                if (!$jp || !$jp->diverifikasi_oleh_bkpsdm) {
                    return response()->json([
                        'message' => 'Pembelajaran belum dapat disetujui karena Jam Pelajaran (JP) belum diverifikasi oleh BKPSDM.'
                    ], 422);
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
