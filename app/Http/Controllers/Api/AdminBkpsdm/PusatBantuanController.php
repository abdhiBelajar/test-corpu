<?php

namespace App\Http\Controllers\Api\AdminBkpsdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PusatBantuanController extends Controller
{
    // TIKET MANAGEMENT
    public function tiket()
    {
        $tiket = \App\Models\HelpTiket::with('pengguna', 'penangan')->get();
        return response()->json([
            'message' => 'Daftar Help Tiket',
            'data' => $tiket
        ]);
    }

    public function tanggapiTiket(\Illuminate\Http\Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:terbuka,diproses,selesai'
        ]);

        $tiket = \App\Models\HelpTiket::findOrFail($id);
        $tiket->update([
            'status' => $request->status,
            'ditangani_oleh_pengguna_id' => $request->user()->pengguna_id,
            'diselesaikan_pada' => $request->status === 'selesai' ? now() : null
        ]);

        return response()->json([
            'message' => 'Status tiket berhasil diupdate',
            'data' => $tiket
        ]);
    }

    // FAQ MANAGEMENT
    public function faq()
    {
        $faq = \App\Models\Faq::orderBy('urutan')->get();
        return response()->json([
            'message' => 'Daftar FAQ',
            'data' => $faq
        ]);
    }

    public function storeFaq(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'pertanyaan' => 'required|string|max:255',
            'jawaban' => 'required|string',
            'kategori' => 'nullable|string',
            'urutan' => 'nullable|integer'
        ]);

        $faq = \App\Models\Faq::create([
            'pertanyaan' => $request->pertanyaan,
            'jawaban' => $request->jawaban,
            'kategori' => $request->kategori,
            'urutan' => $request->urutan ?? 0,
            'dibuat_oleh_pengguna_id' => $request->user()->pengguna_id
        ]);

        return response()->json([
            'message' => 'FAQ berhasil ditambahkan',
            'data' => $faq
        ], 201);
    }

    public function updateFaq(\Illuminate\Http\Request $request, string $id)
    {
        $faq = \App\Models\Faq::findOrFail($id);
        
        $request->validate([
            'pertanyaan' => 'nullable|string|max:255',
            'jawaban' => 'nullable|string',
            'kategori' => 'nullable|string',
            'urutan' => 'nullable|integer'
        ]);

        $faq->update($request->only(['pertanyaan', 'jawaban', 'kategori', 'urutan']));

        return response()->json([
            'message' => 'FAQ berhasil diupdate',
            'data' => $faq
        ]);
    }

    public function destroyFaq(string $id)
    {
        $faq = \App\Models\Faq::findOrFail($id);
        $faq->delete();

        return response()->json([
            'message' => 'FAQ berhasil dihapus'
        ]);
    }
}
