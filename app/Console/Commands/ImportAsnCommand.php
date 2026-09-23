<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PegawaiSimpeg;
use App\Models\Pengguna;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class ImportAsnCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simpeg:import 
                            {file? : Path ke file CSV data ASN} 
                            {--create-accounts : Buatkan juga langsung akun login di tabel pengguna}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data ASN dari file CSV ke basis data SIMPEG lokal (pegawai_simpegs)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');

        if (!$filePath) {
            $defaultPaths = [
                database_path('data/db_peg_bkpsdm_test_lms.csv'),
                base_path('../db_peg_bkpsdm_test_lms.csv'),
            ];

            foreach ($defaultPaths as $path) {
                if (file_exists($path)) {
                    $filePath = $path;
                    break;
                }
            }
        }

        if (!$filePath || !file_exists($filePath)) {
            $this->error("File CSV tidak ditemukan di: " . ($filePath ?: '(belum ditentukan)'));
            return 1;
        }

        $createAccounts = $this->option('create-accounts');
        $this->info("Memulai import data ASN dari: {$filePath}");

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->error("Gagal membuka file: {$filePath}");
            return 1;
        }

        $header = fgetcsv($handle, 1000, ',');
        if (!$header) {
            $this->error("File CSV kosong.");
            fclose($handle);
            return 1;
        }

        $cleanHeader = array_map(function ($col) {
            return strtoupper(trim($col));
        }, $header);

        $totalRows = 0;
        $simpegCount = 0;
        $accountCount = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if (count($row) < count($cleanHeader)) {
                    continue;
                }

                $totalRows++;
                $data = array_combine($cleanHeader, $row);

                $nip = trim($data['NIP'] ?? '');
                $nama = trim($data['NAMA'] ?? '');
                $jabatan = trim($data['JABATAN'] ?? '');
                $kode = strtoupper(trim($data['KODE'] ?? ''));
                $jenisJabatan = strtoupper(trim($data['JENIS JABATAN'] ?? ''));
                $unitKerja = trim($data['UNIT KERJA'] ?? '');

                if (empty($nip) || !is_numeric($nip)) {
                    continue;
                }

                // Normalisasi rumpun_jabatan: JP, JA, JF, JPT
                $rumpun = 'JP';
                if ($kode === 'JPT' || str_contains($jenisJabatan, 'PIMPINAN TINGGI')) {
                    $rumpun = 'JPT';
                } elseif ($kode === 'JA' || str_contains($jenisJabatan, 'ADMINISTRATOR') || str_contains($jenisJabatan, 'PENGAWAS')) {
                    $rumpun = 'JA';
                } elseif ($kode === 'JF' || str_contains($jenisJabatan, 'FUNGSIONAL')) {
                    $rumpun = 'JF';
                } elseif ($kode === 'JP' || str_contains($jenisJabatan, 'PELAKSANA')) {
                    $rumpun = 'JP';
                }

                // 1. Simpan ke database SIMPEG lokal
                PegawaiSimpeg::updateOrCreate(
                    ['nip' => $nip],
                    [
                        'nama_lengkap' => $nama,
                        'jabatan' => $jabatan ?: null,
                        'rumpun_jabatan' => $rumpun,
                        'unit_kerja' => $unitKerja ?: null,
                    ]
                );
                $simpegCount++;

                // 2. Opsi pembuatan akun langsung jika diminta
                if ($createAccounts) {
                    $defaultPassword = Hash::make($nip);
                    $emailPlaceholder = "{$nip}@asn.bulelengkab.go.id";

                    Pengguna::updateOrCreate(
                        ['nip' => $nip],
                        [
                            'nama_lengkap' => $nama,
                            'email' => $emailPlaceholder,
                            'kata_sandi_hash' => $defaultPassword,
                            'peran' => 'peserta',
                            'jabatan' => $jabatan ?: null,
                            'rumpun_jabatan' => $rumpun,
                            'unit_kerja' => $unitKerja ?: null,
                            'status' => 'aktif',
                        ]
                    );
                    $accountCount++;
                }
            }

            DB::commit();
            fclose($handle);

            $this->info("Import berhasil!");
            $this->line("- Total data ASN diproses: {$simpegCount} pegawai terdaftar di data SIMPEG lokal.");
            if ($createAccounts) {
                $this->line("- Total akun dibuat di tabel pengguna: {$accountCount} akun (Password default: NIP).");
            } else {
                $this->line("- ASN sekarang dapat langsung mendaftar via halaman registrasi menggunakan NIP mereka.");
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            $this->error("Terjadi kesalahan saat import: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
