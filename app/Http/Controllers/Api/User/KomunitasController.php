<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Komunitas;

class KomunitasController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Komunitas::withCount(['pembelajaran', 'anggota']);

        // Batasi komunitas sesuai rumpun jabatan peserta (PRD PST-2, Bab 5)
        if (!empty($user->rumpun_jabatan)) {
            $query->where('rumpun_jabatan', $user->rumpun_jabatan);
        }

        $komunitas = $query->get();

        // Ambil daftar komunitas yang di-join oleh user ini
        $joinedKomunitasIds = $user->komunitas()->pluck('komunitas.komunitas_id')->toArray();

        $data = $komunitas->map(function ($k) use ($joinedKomunitasIds) {
            return [
                'id' => $k->komunitas_id,
                'title' => $k->nama_komunitas,
                'description' => $k->deskripsi,
                'category' => $k->rumpun_jabatan,
                'sub_bidang_tersedia' => $k->sub_bidang_tersedia_json ?? [],
                'courses' => $k->pembelajaran_count,
                'members' => $k->anggota_count,
                'is_joined' => in_array($k->komunitas_id, $joinedKomunitasIds),
                // gambar dummy
                'image' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=2070&auto=format&fit=crop',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function join(Request $request, $id)
    {
        $user = $request->user();
        $komunitas = Komunitas::find($id);

        if (!$komunitas) {
            return response()->json(['message' => 'Komunitas tidak ditemukan'], 404);
        }

        // Validasi batasan rumpun jabatan (PRD PST-2, Bab 5)
        if (!empty($user->rumpun_jabatan) && $komunitas->rumpun_jabatan !== $user->rumpun_jabatan) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk bergabung ke komunitas di luar rumpun jabatan Anda (' . $user->rumpun_jabatan . ').'
            ], 403);
        }

        // Khusus rumpun JF: Peserta wajib memilih sub-bidang (PRD PST-3)
        if ($komunitas->rumpun_jabatan === 'JF') {
            $subBidangTersedia = $komunitas->sub_bidang_tersedia_json ?? [];
            if (!empty($subBidangTersedia)) {
                $subBidang = $request->input('sub_bidang');
                if (!$subBidang || !in_array($subBidang, $subBidangTersedia)) {
                    return response()->json([
                        'message' => 'Silakan pilih sub-bidang yang valid untuk komunitas rumpun JF ini.',
                        'sub_bidang_tersedia' => $subBidangTersedia
                    ], 422);
                }
            }
        }

        // Cek apakah sudah bergabung
        if ($user->komunitas()->where('komunitas_pengguna.komunitas_id', $id)->exists()) {
            return response()->json(['message' => 'Anda sudah bergabung di komunitas ini'], 400);
        }

        // Gabung komunitas
        $user->komunitas()->attach($id);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil bergabung dengan komunitas'
        ]);
    }
}
