<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom konfigurasi grid pada tabel kuis
        Schema::table('kuis', function (Blueprint $table) {
            $table->json('grid_config_json')->nullable()->after('tampilkan_kunci_setelah');
        });

        // 2. Tambah dukungan TTS pada tabel soal_kuis
        Schema::table('soal_kuis', function (Blueprint $table) {
            $table->string('tipe_soal', 20)->default('pilihan_ganda')->after('teks_soal');
            $table->string('kunci_jawaban', 255)->change();
            $table->json('pilihan_jawaban_json')->nullable()->change();
            $table->string('arah', 20)->nullable()->after('kunci_jawaban');
            $table->unsignedInteger('nomor_urut')->nullable()->after('arah');
            $table->unsignedInteger('baris_mulai')->nullable()->after('nomor_urut');
            $table->unsignedInteger('kolom_mulai')->nullable()->after('baris_mulai');
        });
    }

    public function down(): void
    {
        Schema::table('soal_kuis', function (Blueprint $table) {
            $table->dropColumn(['tipe_soal', 'arah', 'nomor_urut', 'baris_mulai', 'kolom_mulai']);
            $table->string('kunci_jawaban', 10)->change();
        });

        Schema::table('kuis', function (Blueprint $table) {
            $table->dropColumn('grid_config_json');
        });
    }
};
