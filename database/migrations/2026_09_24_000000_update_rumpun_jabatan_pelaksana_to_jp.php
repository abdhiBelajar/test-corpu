<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update data eksisting dari 'Pelaksana' menjadi 'JP'
        DB::table('pengguna')->where('rumpun_jabatan', 'Pelaksana')->update(['rumpun_jabatan' => 'JP']);
        DB::table('komunitas')->where('rumpun_jabatan', 'Pelaksana')->update(['rumpun_jabatan' => 'JP']);

        // Ubah definisi ENUM kolom rumpun_jabatan
        DB::statement("ALTER TABLE pengguna MODIFY COLUMN rumpun_jabatan ENUM('JPT', 'JA', 'JF', 'JP') NULL");
        DB::statement("ALTER TABLE komunitas MODIFY COLUMN rumpun_jabatan ENUM('JPT', 'JA', 'JF', 'JP') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('pengguna')->where('rumpun_jabatan', 'JP')->update(['rumpun_jabatan' => 'Pelaksana']);
        DB::table('komunitas')->where('rumpun_jabatan', 'JP')->update(['rumpun_jabatan' => 'Pelaksana']);

        DB::statement("ALTER TABLE pengguna MODIFY COLUMN rumpun_jabatan ENUM('JPT', 'JA', 'JF', 'Pelaksana') NULL");
        DB::statement("ALTER TABLE komunitas MODIFY COLUMN rumpun_jabatan ENUM('JPT', 'JA', 'JF', 'Pelaksana') NOT NULL");
    }
};
