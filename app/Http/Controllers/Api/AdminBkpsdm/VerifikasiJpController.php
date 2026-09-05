<?php

namespace App\Http\Controllers\Api\AdminBkpsdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VerifikasiJpController extends Controller
{
    public function index()
    {
        $data = \App\Models\PembelajaranJp::where('diverifikasi_oleh_bkpsdm', false)
            ->with('pembelajaran')
            ->get();
            
        return response()->json([
            'message' => 'Daftar JP Menunggu Verifikasi',
            'data' => $data
        ]);
    }

    public function update(\Illuminate\Http\Request $request, string $pembelajaran_id)
    {
        $request->validate([
            'jp_final' => 'required|numeric|min:0'
        ]);

        $jp = \App\Models\PembelajaranJp::where('pembelajaran_id', $pembelajaran_id)->firstOrFail();
        
        $jp->update([
            'jp_final' => $request->jp_final,
            'diverifikasi_oleh_bkpsdm' => true,
            'diverifikasi_pada' => now()
        ]);

        return response()->json([
            'message' => 'Jam Pelajaran (JP) berhasil diverifikasi',
            'data' => $jp
        ]);
    }
}
