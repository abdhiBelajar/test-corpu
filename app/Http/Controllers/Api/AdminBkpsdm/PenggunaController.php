<?php

namespace App\Http\Controllers\Api\AdminBkpsdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PenggunaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = \App\Models\Pengguna::query();

        if ($request->has('peran') && !empty($request->peran)) {
            $query->where('peran', $request->peran);
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_lengkap', 'like', '%' . $search . '%')
                  ->orWhere('nip', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        // Mendukung paginasi default 25 atau param all=true untuk backward compatibility
        if ($request->boolean('all')) {
            $pengguna = $query->latest('dibuat_pada')->get();
        } else {
            $perPage = (int) $request->input('per_page', 25);
            $pengguna = $query->latest('dibuat_pada')->paginate($perPage);
        }

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

        return \Illuminate\Support\Facades\DB::transaction(function() use ($request) {
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
        });
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
    public function destroy(Request $request, string $id)
    {
        $pengguna = \App\Models\Pengguna::findOrFail($id);

        // 1. Proteksi Anti-Lockout: Cegah menghapus akun sendiri
        if ((string)$pengguna->pengguna_id === (string)$request->user()->pengguna_id) {
            return response()->json([
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri.'
            ], 400);
        }

        // 2. Proteksi Anti-Lockout: Cegah menghapus admin_bkpsdm terakhir
        if ($pengguna->peran === 'admin_bkpsdm') {
            $adminCount = \App\Models\Pengguna::where('peran', 'admin_bkpsdm')->count();
            if ($adminCount <= 1) {
                return response()->json([
                    'message' => 'Tidak dapat menghapus Administrator BKPSDM terakhir di sistem.'
                ], 400);
            }
        }

        $pengguna->delete(); // Soft delete melalui SoftDeletes trait

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
