<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pembelajaran;
use App\Models\PendaftaranPembelajaran;

class KatalogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Pembelajaran::where('status', 'dipublikasikan')
            ->withCount('modul')
            ->with('jp');

        if ($request->has('kategori') && $request->kategori !== 'Semua Kategori') {
            $query->where('kategori', $request->kategori);
        }

        if ($request->has('search') && !empty($request->search)) {
            $query->where(function($q) use ($request) {
                $q->where('judul_pembelajaran', 'like', '%' . $request->search . '%')
                  ->orWhere('deskripsi', 'like', '%' . $request->search . '%');
            });
        }

        $sort = $request->input('sort', 'terbaru');
        if ($sort === 'terbaru') {
            $query->latest('dipublikasikan_pada');
        } else {
            $query->orderBy('judul_pembelajaran', 'asc');
        }

        $pembelajaran = $query->paginate(12);

        // Map to add enrolled status
        $enrolledIds = PendaftaranPembelajaran::where('pengguna_id', $user->pengguna_id)->pluck('pembelajaran_id')->toArray();

        $items = $pembelajaran->getCollection()->map(function ($item) use ($enrolledIds) {
            return [
                'id' => $item->pembelajaran_id,
                'image' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?q=80&w=2070&auto=format&fit=crop', // placeholder
                'category' => $item->kategori ?? 'Lainnya',
                'title' => $item->judul_pembelajaran,
                'description' => $item->deskripsi,
                'jpl' => $item->jp->jp_final ?? 0,
                'modules' => $item->modul_count,
                'isEnrolled' => in_array($item->pembelajaran_id, $enrolledIds)
            ];
        });

        $pembelajaran->setCollection($items);

        return response()->json([
            'message' => 'Katalog berhasil diambil',
            'data' => $pembelajaran
        ]);
    }

    public function enroll(Request $request, $id)
    {
        $user = $request->user();
        
        $pembelajaran = Pembelajaran::where('status', 'dipublikasikan')->findOrFail($id);

        $exists = PendaftaranPembelajaran::where('pengguna_id', $user->pengguna_id)
            ->where('pembelajaran_id', $id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Anda sudah terdaftar di pelatihan ini'], 400);
        }

        PendaftaranPembelajaran::create([
            'pengguna_id' => $user->pengguna_id,
            'pembelajaran_id' => $id,
            'status_pendaftaran' => 'terdaftar',
            'persentase_progres' => 0,
            'terdaftar_pada' => now()
        ]);

        return response()->json([
            'message' => 'Berhasil mendaftar ke pelatihan'
        ]);
    }
}
