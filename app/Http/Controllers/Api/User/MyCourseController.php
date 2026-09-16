<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PendaftaranPembelajaran;

class MyCourseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $status = $request->input('status', 'all');

        $query = PendaftaranPembelajaran::where('pengguna_id', $user->pengguna_id)
            ->with(['pembelajaran' => function($q) {
                $q->withCount('modul')->with('pembelajaranJp');
            }]);

        if ($status !== 'all') {
            // Mapping UI status to backend status
            // 'berjalan' => 'sedang_berjalan'
            // 'selesai' => 'selesai', 'lulus'
            // 'menunggu' => 'menunggu_post_test'
            if ($status === 'berjalan') {
                $query->whereIn('status_pendaftaran', ['terdaftar', 'sedang_berjalan', 'menunggu_post_test']);
            } elseif ($status === 'selesai') {
                $query->whereIn('status_pendaftaran', ['selesai', 'lulus']);
            } elseif ($status === 'menunggu') {
                $query->where('status_pendaftaran', 'menunggu_post_test');
            } else {
                $query->where('status_pendaftaran', $status);
            }
        }

        $sort = $request->input('sort', 'terbaru');
        if ($sort === 'terbaru') {
            $query->latest('terdaftar_pada');
        } else {
            // Need to join to sort by title, for simplicity we sort the collection after
        }

        $pendaftaran = $query->get();

        if ($sort === 'abjad') {
            $pendaftaran = $pendaftaran->sortBy(function($p) {
                return $p->pembelajaran->judul_pembelajaran ?? '';
            })->values();
        }

        $items = $pendaftaran->map(function($item) {
            $jp = $item->pembelajaran->pembelajaranJp->first();
            return [
                'pendaftaran_id' => $item->pendaftaran_id,
                'pembelajaran_id' => $item->pembelajaran_id,
                'image' => $item->pembelajaran->thumbnail_url,
                'thumbnail_url' => $item->pembelajaran->thumbnail_url,
                'category' => $item->pembelajaran->kategori ?? 'Lainnya',
                'title' => $item->pembelajaran->judul_pembelajaran,
                'description' => $item->pembelajaran->deskripsi,
                'jpl' => $jp ? $jp->jp_final : 0,
                'modules' => $item->pembelajaran->modul_count,
                'progress' => $item->persentase_progres,
                'status' => $item->status_pendaftaran,
                'terdaftar_pada' => $item->terdaftar_pada
            ];
        });

        // Grouping for frontend "sedang_berjalan", "selesai" stats
        $allUserPendaftaran = PendaftaranPembelajaran::where('pengguna_id', $user->pengguna_id)->get();
        $stats = [
            'total' => $allUserPendaftaran->count(),
            'berjalan' => $allUserPendaftaran->whereIn('status_pendaftaran', ['terdaftar', 'sedang_berjalan', 'menunggu_post_test'])->count(),
            'menunggu' => $allUserPendaftaran->where('status_pendaftaran', 'menunggu_post_test')->count(),
            'selesai' => $allUserPendaftaran->whereIn('status_pendaftaran', ['selesai', 'lulus'])->count(),
        ];

        return response()->json([
            'message' => 'Daftar pelatihanku berhasil diambil',
            'data' => [
                'courses' => $items,
                'stats' => $stats
            ]
        ]);
    }
}
