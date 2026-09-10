<?php

namespace App\Http\Controllers\Api\AdminKomunitas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HelpTiket;
use App\Models\Faq;

class PusatBantuanController extends Controller
{
    public function faq()
    {
        $faqs = Faq::orderBy('urutan')->get();
        return response()->json([
            'message' => 'Daftar FAQ berhasil diambil',
            'data' => $faqs
        ]);
    }

    public function storeTiket(Request $request)
    {
        $request->validate([
            'subjek' => 'required|string|max:200',
            'deskripsi' => 'required|string',
        ]);

        $tiket = HelpTiket::create([
            'pengguna_id' => $request->user()->pengguna_id,
            'subjek' => $request->subjek,
            'deskripsi' => $request->deskripsi,
            'status' => 'terbuka',
        ]);

        return response()->json([
            'message' => 'Keluhan / tiket bantuan berhasil dikirim',
            'data' => $tiket
        ], 201);
    }

    public function myTiket(Request $request)
    {
        $tikets = HelpTiket::where('pengguna_id', $request->user()->pengguna_id)
            ->latest('dibuat_pada')
            ->get();

        return response()->json([
            'message' => 'Daftar tiket saya berhasil diambil',
            'data' => $tikets
        ]);
    }
}
