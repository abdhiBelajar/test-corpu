<?php

namespace App\Http\Controllers\Api\AdminBkpsdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class KomunitasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $komunitas = \App\Models\Komunitas::all();
        return response()->json([
            'message' => 'Daftar Komunitas',
            'data' => $komunitas
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_komunitas' => 'required|string|unique:komunitas',
            'deskripsi' => 'nullable|string',
            'rumpun_jabatan' => 'required|in:JPT,JA,JF,Pelaksana',
            'sub_bidang_tersedia_json' => 'nullable|array'
        ]);

        $komunitas = \App\Models\Komunitas::create([
            'nama_komunitas' => $request->nama_komunitas,
            'deskripsi' => $request->deskripsi,
            'rumpun_jabatan' => $request->rumpun_jabatan,
            'sub_bidang_tersedia_json' => $request->sub_bidang_tersedia_json,
            'dibuat_oleh_pengguna_id' => $request->user()->pengguna_id,
            'status' => 'aktif'
        ]);

        return response()->json([
            'message' => 'Komunitas berhasil dibuat',
            'data' => $komunitas
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $komunitas = \App\Models\Komunitas::findOrFail($id);
        return response()->json([
            'message' => 'Detail Komunitas',
            'data' => $komunitas
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $komunitas = \App\Models\Komunitas::findOrFail($id);

        $request->validate([
            'nama_komunitas' => 'nullable|string|unique:komunitas,nama_komunitas,' . $id . ',komunitas_id',
            'deskripsi' => 'nullable|string',
            'rumpun_jabatan' => 'nullable|in:JPT,JA,JF,Pelaksana',
            'sub_bidang_tersedia_json' => 'nullable|array',
            'status' => 'nullable|in:aktif,nonaktif'
        ]);

        $komunitas->update($request->only([
            'nama_komunitas', 'deskripsi', 'rumpun_jabatan', 'sub_bidang_tersedia_json', 'status'
        ]));

        return response()->json([
            'message' => 'Komunitas berhasil diupdate',
            'data' => $komunitas
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $komunitas = \App\Models\Komunitas::findOrFail($id);
        $komunitas->delete();

        return response()->json([
            'message' => 'Komunitas berhasil dihapus'
        ]);
    }
}
