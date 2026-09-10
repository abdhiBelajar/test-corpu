<?php

namespace App\Http\Controllers\Api\AdminBkpsdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ValidasiPembelajaranController extends Controller
{
    public function index()
    {
        $pembelajaran = \App\Models\Pembelajaran::where('status', 'menunggu_approval')->get();
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

        $pembelajaran = \App\Models\Pembelajaran::where('status', 'menunggu_approval')->findOrFail($pembelajaran_id);

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
