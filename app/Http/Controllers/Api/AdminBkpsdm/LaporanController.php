<?php

namespace App\Http\Controllers\Api\AdminBkpsdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    /**
     * Helper method to get formatted and filtered peserta data
     */
    protected function getPesertaData(Request $request)
    {
        $hasCourseFilter = $request->filled('pembelajaran_id') || $request->filled('komunitas_id') || $request->filled('status_sertifikat');

        $pendaftaranQuery = \App\Models\PendaftaranPembelajaran::with([
            'pengguna',
            'pembelajaran.komunitas',
            'sertifikat'
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $pendaftaranQuery->whereHas('pengguna', function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        if ($request->filled('komunitas_id')) {
            $pendaftaranQuery->whereHas('pembelajaran', function ($q) use ($request) {
                $q->where('komunitas_id', $request->komunitas_id);
            });
        }

        if ($request->filled('pembelajaran_id')) {
            $pendaftaranQuery->where('pembelajaran_id', $request->pembelajaran_id);
        }

        if ($request->filled('status_sertifikat')) {
            $val = strtolower($request->status_sertifikat);
            if (in_array($val, ['1', 'ada', 'true', 'punya', 'terbit'])) {
                $pendaftaranQuery->whereHas('sertifikat');
            } elseif (in_array($val, ['0', 'tidak', 'false', 'belum'])) {
                $pendaftaranQuery->doesntHave('sertifikat');
            }
        }

        if ($request->filled('tanggal_mulai')) {
            $pendaftaranQuery->whereDate('terdaftar_pada', '>=', $request->tanggal_mulai);
        }

        if ($request->filled('tanggal_selesai')) {
            $pendaftaranQuery->whereDate('terdaftar_pada', '<=', $request->tanggal_selesai);
        }

        $pendaftarans = $pendaftaranQuery->latest('pendaftaran_id')->get();

        $items = $pendaftarans->map(function ($p) {
            return [
                'pendaftaran_id' => $p->pendaftaran_id,
                'pengguna_id' => $p->pengguna_id,
                'nama_lengkap' => $p->pengguna?->nama_lengkap ?? '-',
                'nip' => $p->pengguna?->nip ?? '-',
                'jabatan' => $p->pengguna?->jabatan ?? '-',
                'instansi' => $p->pengguna?->instansi ?? '-',
                'unit_kerja' => $p->pengguna?->unit_kerja ?? '-',
                'rumpun_jabatan' => $p->pengguna?->rumpun_jabatan ?? '-',
                'judul_pembelajaran' => $p->pembelajaran?->judul_pembelajaran ?? '-',
                'nama_komunitas' => $p->pembelajaran?->komunitas?->nama_komunitas ?? 'BKPSDM',
                'jenis_pelatihan' => $p->pembelajaran?->kategori ?? 'Pengembangan Kompetensi',
                'progres' => (float) ($p->persentase_progres ?? 0),
                'status_pendaftaran' => $p->status_pendaftaran,
                'tanggal_daftar' => $p->terdaftar_pada,
                'tanggal_selesai' => $p->diselesaikan_pada,
                'has_sertifikat' => !is_null($p->sertifikat),
                'sertifikat' => $p->sertifikat ? [
                    'sertifikat_id' => $p->sertifikat->sertifikat_id,
                    'nomor_sertifikat' => $p->sertifikat->nomor_sertifikat,
                    'file_sertifikat_path' => $p->sertifikat->file_sertifikat_path,
                    'diterbitkan_pada' => $p->sertifikat->diterbitkan_pada,
                    'download_url' => url('/api/user/certificates/' . $p->sertifikat->sertifikat_id . '/download'),
                ] : null,
                'pengguna' => $p->pengguna,
                'pembelajaran' => $p->pembelajaran,
            ];
        });

        // If no course/community filter is specified, also append participants with no enrollments
        if (!$hasCourseFilter) {
            $registeredUserIds = $pendaftarans->pluck('pengguna_id')->unique()->toArray();
            $unregisteredQuery = \App\Models\Pengguna::where('peran', 'peserta')
                ->whereNotIn('pengguna_id', $registeredUserIds);

            if ($request->filled('search')) {
                $search = $request->search;
                $unregisteredQuery->where(function ($q) use ($search) {
                    $q->where('nama_lengkap', 'like', "%{$search}%")
                      ->orWhere('nip', 'like', "%{$search}%");
                });
            }

            $unregistered = $unregisteredQuery->latest('pengguna_id')->get();
            foreach ($unregistered as $u) {
                $items->push([
                    'pendaftaran_id' => null,
                    'pengguna_id' => $u->pengguna_id,
                    'nama_lengkap' => $u->nama_lengkap,
                    'nip' => $u->nip ?? '-',
                    'jabatan' => $u->jabatan ?? '-',
                    'instansi' => $u->instansi ?? '-',
                    'unit_kerja' => $u->unit_kerja ?? '-',
                    'rumpun_jabatan' => $u->rumpun_jabatan ?? '-',
                    'judul_pembelajaran' => 'Belum terdaftar',
                    'nama_komunitas' => 'BKPSDM',
                    'jenis_pelatihan' => '-',
                    'progres' => 0,
                    'status_pendaftaran' => 'Belum Mulai',
                    'tanggal_daftar' => null,
                    'tanggal_selesai' => null,
                    'has_sertifikat' => false,
                    'sertifikat' => null,
                    'pengguna' => $u,
                    'pembelajaran' => null,
                ]);
            }
        }

        return $items;
    }

    public function peserta(Request $request)
    {
        $items = $this->getPesertaData($request);

        if ($request->has('page') || $request->has('per_page')) {
            $perPage = (int) $request->input('per_page', 10);
            $page = (int) $request->input('page', 1);
            $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();

            return response()->json([
                'message' => 'Laporan Data Peserta',
                'data' => $slice,
                'meta' => [
                    'current_page' => $page,
                    'last_page' => max(1, (int) ceil($items->count() / $perPage)),
                    'per_page' => $perPage,
                    'total' => $items->count(),
                ]
            ]);
        }

        return response()->json([
            'message' => 'Laporan Data Peserta',
            'data' => $items
        ]);
    }

    public function exportPeserta(Request $request)
    {
        $items = $this->getPesertaData($request);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="laporan_peserta.csv"',
        ];

        // Defense against Formula Injection (CSV injection)
        $sanitize = function ($value) {
            if ($value === null) return '';
            $str = (string) $value;
            if (preg_match('/^([=+\-@\t\r])/', $str)) {
                return "'" . $str;
            }
            return $str;
        };

        $callback = function () use ($items, $sanitize) {
            $file = fopen('php://output', 'w');
            // Write UTF-8 BOM so Excel opens strings/characters cleanly
            fputs($file, "\xEF\xBB\xBF");

            fputcsv($file, [
                'NIP',
                'Nama Lengkap',
                'Jabatan',
                'Rumpun',
                'Unit Kerja',
                'Pembelajaran',
                'Komunitas',
                'Jenis Pelatihan',
                'Progres (%)',
                'Status',
                'Tanggal Selesai',
                'Nomor Sertifikat'
            ]);

            foreach ($items as $row) {
                fputcsv($file, [
                    $sanitize($row['nip']),
                    $sanitize($row['nama_lengkap']),
                    $sanitize($row['jabatan']),
                    $sanitize($row['rumpun_jabatan']),
                    $sanitize($row['unit_kerja']),
                    $sanitize($row['judul_pembelajaran']),
                    $sanitize($row['nama_komunitas']),
                    $sanitize($row['jenis_pelatihan']),
                    $sanitize($row['progres']),
                    $sanitize($row['status_pendaftaran']),
                    $sanitize($row['tanggal_selesai']),
                    $sanitize($row['sertifikat'] ? $row['sertifikat']['nomor_sertifikat'] : '-'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
