<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kategori_kursus', function (Blueprint $table) {
            $table->id('kategori_id');
            $table->string('nama_kategori')->unique();
            $table->text('deskripsi')->nullable();
            $table->timestamp('dibuat_pada')->useCurrent();
            $table->timestamp('diperbarui_pada')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        });

        // Seed 4 initial default categories
        $now = now();
        $defaults = [
            [
                'nama_kategori' => 'Manajemen ASN',
                'deskripsi' => 'Kursus seputar tata kelola, kebijakan, kepangkatan, dan manajemen aparatur sipil negara.',
                'dibuat_pada' => $now,
                'diperbarui_pada' => $now,
            ],
            [
                'nama_kategori' => 'Teknologi Informasi',
                'deskripsi' => 'Pengembangan keterampilan digital, sistem informasi, keamanan siber, dan teknologi perkantoran.',
                'dibuat_pada' => $now,
                'diperbarui_pada' => $now,
            ],
            [
                'nama_kategori' => 'Pengembangan Kompetensi',
                'deskripsi' => 'Pelatihan peningkatan kepemimpinan, komunikasi, integritas, dan kompetensi teknis.',
                'dibuat_pada' => $now,
                'diperbarui_pada' => $now,
            ],
            [
                'nama_kategori' => 'Pelayanan Publik',
                'deskripsi' => 'Peningkatan mutu layanan publik, etika pelayanan prima, dan penanganan pengaduan masyarakat.',
                'dibuat_pada' => $now,
                'diperbarui_pada' => $now,
            ],
        ];

        DB::table('kategori_kursus')->insert($defaults);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kategori_kursus');
    }
};
