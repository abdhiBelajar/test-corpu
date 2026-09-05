<?php

namespace App\Http\Controllers\Api\AdminBkpsdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function peserta()
    {
        $peserta = \App\Models\Pengguna::where('peran', 'peserta')->get();
        return response()->json([
            'message' => 'Laporan Data Peserta',
            'data' => $peserta
        ]);
    }

    public function exportPeserta()
    {
        $peserta = \App\Models\Pengguna::where('peran', 'peserta')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="laporan_peserta.csv"',
        ];

        $callback = function () use ($peserta) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['NIP', 'Nama Lengkap', 'Jabatan', 'Rumpun', 'Unit Kerja']);
            
            foreach ($peserta as $p) {
                fputcsv($file, [
                    $p->nip,
                    $p->nama_lengkap,
                    $p->jabatan,
                    $p->rumpun_jabatan,
                    $p->unit_kerja
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
