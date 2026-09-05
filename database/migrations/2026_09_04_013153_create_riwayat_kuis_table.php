<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_kuis', function (Blueprint $table) {
            $table->id('riwayat_kuis_id');
            $table->unsignedBigInteger('kuis_id');
            $table->unsignedBigInteger('pendaftaran_id');
            $table->unsignedInteger('percobaan_ke'); 
            $table->decimal('nilai', 5, 2);
            $table->boolean('apakah_lulus');
            $table->json('jawaban_peserta_json');
            $table->json('snapshot_soal_json');
            $table->timestamp('dikerjakan_pada')->useCurrent();

            $table->foreign('kuis_id')->references('kuis_id')->on('kuis')->onDelete('cascade');
            $table->foreign('pendaftaran_id')->references('pendaftaran_id')->on('pendaftaran_pembelajaran')->onDelete('cascade');
            $table->unique(['kuis_id', 'pendaftaran_id', 'percobaan_ke']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_kuis');
    }
};
