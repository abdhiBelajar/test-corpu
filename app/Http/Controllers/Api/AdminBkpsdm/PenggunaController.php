<?php

namespace App\Http\Controllers\Api\AdminBkpsdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PenggunaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pengguna = \App\Models\Pengguna::all();
        return response()->json([
            'message' => 'Daftar Pengguna',
            'data' => $pengguna
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nip' => 'required|string|unique:pengguna',
            'nama_lengkap' => 'required|string',
            'email' => 'nullable|email|unique:pengguna',
            'peran' => 'required|in:admin_bkpsdm,admin_komunitas,peserta',
            'jabatan' => 'nullable|string',
            'rumpun_jabatan' => 'nullable|in:JPT,JA,JF,Pelaksana',
            'unit_kerja' => 'nullable|string',
            'komunitas_id' => 'required_if:peran,admin_komunitas|exists:komunitas,komunitas_id'
        ]);

        $passwordDefault = substr($request->nip, -8);
        $pengguna = \App\Models\Pengguna::create([
            'nip' => $request->nip,
            'nama_lengkap' => $request->nama_lengkap,
            'email' => $request->email,
            'kata_sandi_hash' => \Illuminate\Support\Facades\Hash::make($passwordDefault),
            'peran' => $request->peran,
            'jabatan' => $request->jabatan,
            'rumpun_jabatan' => $request->rumpun_jabatan,
            'unit_kerja' => $request->unit_kerja,
            'status' => 'aktif'
        ]);

        if ($request->peran === 'admin_komunitas' && $request->has('komunitas_id')) {
            \App\Models\AdminKomunitas::create([
                'pengguna_id' => $pengguna->pengguna_id,
                'komunitas_id' => $request->komunitas_id
            ]);
        }

        return response()->json([
            'message' => 'Pengguna berhasil ditambahkan',
            'data' => $pengguna
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pengguna = \App\Models\Pengguna::findOrFail($id);
        return response()->json([
            'message' => 'Detail Pengguna',
            'data' => $pengguna
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'peran' => 'nullable|in:admin_bkpsdm,admin_komunitas,peserta',
            'status' => 'nullable|in:aktif,nonaktif',
            'komunitas_id' => 'required_if:peran,admin_komunitas|exists:komunitas,komunitas_id'
        ]);

        $pengguna = \App\Models\Pengguna::findOrFail($id);
        
        if ($request->has('peran')) {
            $pengguna->peran = $request->peran;
            
            if ($request->peran === 'admin_komunitas') {
                if ($request->has('komunitas_id')) {
                    \App\Models\AdminKomunitas::updateOrCreate(
                        ['pengguna_id' => $pengguna->pengguna_id],
                        ['komunitas_id' => $request->komunitas_id]
                    );
                }
            } else {
                \App\Models\AdminKomunitas::where('pengguna_id', $pengguna->pengguna_id)->delete();
            }
        }
        
        if ($request->has('status')) {
            $pengguna->status = $request->status;
        }
        
        $pengguna->save();

        return response()->json([
            'message' => 'Data pengguna berhasil diubah',
            'data' => $pengguna
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $pengguna = \App\Models\Pengguna::findOrFail($id);
        $pengguna->delete();

        return response()->json([
            'message' => 'Pengguna berhasil dihapus'
        ]);
    }

    public function resetPassword(string $id)
    {
        $pengguna = \App\Models\Pengguna::findOrFail($id);
        $passwordDefault = substr($pengguna->nip, -8);
        
        $pengguna->update([
            'kata_sandi_hash' => \Illuminate\Support\Facades\Hash::make($passwordDefault)
        ]);

        return response()->json([
            'message' => 'Password pengguna berhasil direset ke default (8 digit NIP).'
        ]);
    }
}
