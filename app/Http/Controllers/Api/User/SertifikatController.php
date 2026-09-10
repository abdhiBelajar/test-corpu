<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sertifikat;

class SertifikatController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $sertifikat = Sertifikat::whereHas('pendaftaran', function($q) use ($user) {
            $q->where('pengguna_id', $user->pengguna_id);
        })
        ->with(['pendaftaran.pembelajaran' => function($q) {
            $q->with('jp');
        }])
        ->orderBy('tanggal_terbit', 'desc')
        ->get()
        ->map(function($s) {
            return [
                'sertifikat_id' => $s->sertifikat_id,
                'nomor_sertifikat' => $s->nomor_sertifikat,
                'tanggal_terbit' => $s->tanggal_terbit,
                'judul_pelatihan' => $s->pendaftaran->pembelajaran->judul_pembelajaran ?? '-',
                'jpl' => $s->pendaftaran->pembelajaran->jp->jp_final ?? 0,
                'kategori' => $s->pendaftaran->pembelajaran->kategori ?? 'Lainnya',
                'tautan_berkas' => $s->tautan_berkas,
                'image' => 'https://images.unsplash.com/photo-1542744173-8e7e53415bb0?q=80&w=2070&auto=format&fit=crop'
            ];
        });

        return response()->json([
            'message' => 'Daftar sertifikat berhasil diambil',
            'data' => $sertifikat
        ]);
    }
}
