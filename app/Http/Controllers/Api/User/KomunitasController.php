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

        // Ambil semua komunitas beserta jumlah course & member
        $komunitas = Komunitas::withCount(['pembelajaran', 'anggota'])->get();

        // Ambil daftar komunitas yang di-join oleh user ini
        $joinedKomunitasIds = $user->komunitas()->pluck('komunitas.komunitas_id')->toArray();

        $data = $komunitas->map(function ($k) use ($joinedKomunitasIds) {
            return [
                'id' => $k->komunitas_id,
                'title' => $k->nama_komunitas,
                'description' => $k->deskripsi,
                'category' => $k->rumpun_jabatan,
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
