<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminKomunitasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminKomunitas = \App\Models\Pengguna::create([
            'nama_lengkap' => 'Admin Komunitas JF Kesehatan',
            'nip' => '199001012026011002',
            'kata_sandi_hash' => \Illuminate\Support\Facades\Hash::make('admin123'),
            'peran' => 'admin_komunitas',
            'rumpun_jabatan' => 'JF',
            'sub_bidang_jf' => 'Kesehatan',
            'status' => 'aktif',
        ]);

        $adminBkpsdm = \App\Models\Pengguna::where('peran', 'admin_bkpsdm')->first();
        $bkpsdmId = $adminBkpsdm ? $adminBkpsdm->pengguna_id : $adminKomunitas->pengguna_id;

        $komunitas = \App\Models\Komunitas::create([
            'dibuat_oleh_pengguna_id' => $bkpsdmId,
            'nama_komunitas' => 'Komunitas JF Kesehatan',
            'deskripsi' => 'Komunitas Belajar untuk Jabatan Fungsional Kesehatan',
            'rumpun_jabatan' => 'JF',
            'sub_bidang_tersedia_json' => json_encode(['Kesehatan']),
            'status' => 'aktif',
        ]);

        \App\Models\AdminKomunitas::create([
            'komunitas_id' => $komunitas->komunitas_id,
            'pengguna_id' => $adminKomunitas->pengguna_id,
        ]);
    }
}
