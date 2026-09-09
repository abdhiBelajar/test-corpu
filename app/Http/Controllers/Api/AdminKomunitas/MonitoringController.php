<?php

namespace App\Http\Controllers\Api\AdminKomunitas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    private function isPembelajaranAdmin($user, $pembelajaran_id) {
        $pembelajaran = \App\Models\Pembelajaran::find($pembelajaran_id);
        if (!$pembelajaran) return false;

        return \App\Models\AdminKomunitas::where('pengguna_id', $user->pengguna_id)
                    ->where('komunitas_id', $pembelajaran->komunitas_id)
                    ->exists();
    }

    public function index(Request $request, $pembelajaran_id)
    {
        if (!$this->isPembelajaranAdmin($request->user(), $pembelajaran_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $peserta = \App\Models\PendaftaranPembelajaran::where('pembelajaran_id', $pembelajaran_id)
                        ->with(['pengguna' => function($q) {
                            $q->select('pengguna_id', 'nama_lengkap', 'nip', 'rumpun_jabatan', 'unit_kerja', 'jabatan');
                        }])
                        ->get();

        return response()->json([
            'message' => 'Daftar peserta berhasil diambil',
            'data' => $peserta
        ]);
    }

}

