<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HelpTiket;

class BantuanController extends Controller
{
    public function submitTiket(Request $request)
    {
        $request->validate([
            'subjek' => 'required|string|max:200',
            'deskripsi' => 'required|string',
        ]);

        $user = $request->user();

        // Menyimpan keluhan ke tabel help_tiket
        $tiket = HelpTiket::create([
            'pengguna_id' => $user->pengguna_id,
            'subjek' => $request->subjek,
            'deskripsi' => $request->deskripsi,
            'status' => 'terbuka',
        ]);

        return response()->json([
            'message' => 'Keluhan berhasil dikirim. Tim kami akan segera merespons.',
            'data' => $tiket
        ], 201);
    }
}
