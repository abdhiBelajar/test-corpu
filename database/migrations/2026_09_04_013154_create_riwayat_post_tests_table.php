<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_post_test', function (Blueprint $table) {
            $table->id('riwayat_post_test_id');
            $table->unsignedBigInteger('post_test_id');
            $table->unsignedBigInteger('pendaftaran_id');
            $table->unsignedInteger('percobaan_ke'); 
            $table->decimal('nilai', 5, 2);
            $table->boolean('apakah_lulus');
            $table->json('jawaban_peserta_json');
            $table->json('snapshot_soal_json');
            $table->timestamp('dikirim_pada')->useCurrent();

            $table->foreign('post_test_id')->references('post_test_id')->on('post_test')->onDelete('cascade');
            $table->foreign('pendaftaran_id')->references('pendaftaran_id')->on('pendaftaran_pembelajaran')->onDelete('cascade');
            $table->unique(['post_test_id', 'pendaftaran_id', 'percobaan_ke'], 'riwayat_post_test_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_post_test');
    }
};
