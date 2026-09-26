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
        // 1. Perluas definisi ENUM terlebih dahulu agar mencakup 'Pelaksana' dan 'JP'
        DB::statement("ALTER TABLE pengguna MODIFY COLUMN rumpun_jabatan ENUM('JPT', 'JA', 'JF', 'Pelaksana', 'JP') NULL");
        DB::statement("ALTER TABLE komunitas MODIFY COLUMN rumpun_jabatan ENUM('JPT', 'JA', 'JF', 'Pelaksana', 'JP') NOT NULL");

        // 2. Update data eksisting dari 'Pelaksana' menjadi 'JP'
        DB::table('pengguna')->where('rumpun_jabatan', 'Pelaksana')->update(['rumpun_jabatan' => 'JP']);
        DB::table('komunitas')->where('rumpun_jabatan', 'Pelaksana')->update(['rumpun_jabatan' => 'JP']);

        // 3. Ubah definisi ENUM kolom rumpun_jabatan hanya menyisakan 'JP'
        DB::statement("ALTER TABLE pengguna MODIFY COLUMN rumpun_jabatan ENUM('JPT', 'JA', 'JF', 'JP') NULL");
        DB::statement("ALTER TABLE komunitas MODIFY COLUMN rumpun_jabatan ENUM('JPT', 'JA', 'JF', 'JP') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Perluas definisi ENUM terlebih dahulu agar mencakup 'JP' dan 'Pelaksana'
        DB::statement("ALTER TABLE pengguna MODIFY COLUMN rumpun_jabatan ENUM('JPT', 'JA', 'JF', 'Pelaksana', 'JP') NULL");
        DB::statement("ALTER TABLE komunitas MODIFY COLUMN rumpun_jabatan ENUM('JPT', 'JA', 'JF', 'Pelaksana', 'JP') NOT NULL");

        // 2. Kembalikan data dari 'JP' menjadi 'Pelaksana'
        DB::table('pengguna')->where('rumpun_jabatan', 'JP')->update(['rumpun_jabatan' => 'Pelaksana']);
        DB::table('komunitas')->where('rumpun_jabatan', 'JP')->update(['rumpun_jabatan' => 'Pelaksana']);

        // 3. Kembalikan definisi ENUM kolom rumpun_jabatan
        DB::statement("ALTER TABLE pengguna MODIFY COLUMN rumpun_jabatan ENUM('JPT', 'JA', 'JF', 'Pelaksana') NULL");
        DB::statement("ALTER TABLE komunitas MODIFY COLUMN rumpun_jabatan ENUM('JPT', 'JA', 'JF', 'Pelaksana') NOT NULL");
    }
};
