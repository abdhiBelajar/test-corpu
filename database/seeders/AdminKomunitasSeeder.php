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
        $adminKomunitas = \App\Models\Pengguna::updateOrCreate(
            ['nip' => '199001012026011002'],
            [
                'nama_lengkap' => 'Admin Komunitas JF Kesehatan',
                'kata_sandi_hash' => \Illuminate\Support\Facades\Hash::make('admin123'),
                'peran' => 'admin_komunitas',
                'rumpun_jabatan' => 'JF',
                'status' => 'aktif',
            ]
        );

        $adminBkpsdm = \App\Models\Pengguna::where('peran', 'admin_bkpsdm')->first();
        $bkpsdmId = $adminBkpsdm ? $adminBkpsdm->pengguna_id : $adminKomunitas->pengguna_id;

        $komunitas = \App\Models\Komunitas::updateOrCreate(
            ['nama_komunitas' => 'Komunitas JF Kesehatan'],
            [
                'dibuat_oleh_pengguna_id' => $bkpsdmId,
                'deskripsi' => 'Komunitas Belajar untuk Jabatan Fungsional Kesehatan',
                'rumpun_jabatan' => 'JF',
                'status' => 'aktif',
            ]
        );

        \App\Models\AdminKomunitas::firstOrCreate([
            'komunitas_id' => $komunitas->komunitas_id,
            'pengguna_id' => $adminKomunitas->pengguna_id,
        ]);
    }
}
