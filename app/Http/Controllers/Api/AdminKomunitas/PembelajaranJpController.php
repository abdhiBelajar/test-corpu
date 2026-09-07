<?php

namespace App\Http\Controllers\Api\AdminKomunitas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PembelajaranJpController extends Controller
{
    private function isPembelajaranAdmin($user, $pembelajaran_id) {
        $pembelajaran = \App\Models\Pembelajaran::find($pembelajaran_id);
        if (!$pembelajaran) return false;

        return \App\Models\AdminKomunitas::where('pengguna_id', $user->pengguna_id)
                    ->where('komunitas_id', $pembelajaran->komunitas_id)
                    ->exists();
    }

    public function store(Request $request, $pembelajaran_id)
    {
        if (!$this->isPembelajaranAdmin($request->user(), $pembelajaran_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $pembelajaran = \App\Models\Pembelajaran::find($pembelajaran_id);
        if ($pembelajaran->status === 'dipublikasikan') {
            return response()->json(['message' => 'Tidak dapat mengubah JP pada pembelajaran yang sudah dipublikasikan.'], 400);
        }

        $request->validate([
            'jenis_pelatihan' => 'required|string|max:100',
        ]);

        // Hitung ulang dari semua modul
        $totalDurasi = \App\Models\Modul::where('pembelajaran_id', $pembelajaran_id)->sum('durasi_total_menit');
        $totalJp = \App\Models\Modul::where('pembelajaran_id', $pembelajaran_id)->sum('jp_modul');

        $pembelajaranJp = \App\Models\PembelajaranJp::updateOrCreate(
            ['pembelajaran_id' => $pembelajaran_id],
            [
                'jenis_pelatihan' => $request->jenis_pelatihan,
                'total_durasi_menit' => $totalDurasi,
                'total_jp' => $totalJp,
            ]
        );

        return response()->json([
            'message' => 'Data JP berhasil disimpan',
            'data' => $pembelajaranJp
        ]);
    }

}