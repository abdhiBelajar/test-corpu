<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pendaftaran_pembelajaran', function (Blueprint $table) {
            $table->id('pendaftaran_id');
            $table->unsignedBigInteger('pengguna_id');
            $table->unsignedBigInteger('pembelajaran_id');
            $table->string('sub_bidang_dipilih', 100)->nullable();
            $table->decimal('persentase_progres', 5, 2)->default(0);
            $table->enum('status_pendaftaran', ['terdaftar', 'sedang_berjalan', 'menunggu_post_test', 'lulus', 'tidak_lulus', 'selesai'])->default('terdaftar');
            $table->timestamp('terdaftar_pada')->useCurrent();
            $table->timestamp('diselesaikan_pada')->nullable();

            $table->foreign('pengguna_id')->references('pengguna_id')->on('pengguna')->onDelete('cascade');
            $table->foreign('pembelajaran_id')->references('pembelajaran_id')->on('pembelajaran')->onDelete('cascade');
            $table->unique(['pengguna_id', 'pembelajaran_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendaftaran_pembelajaran');
    }
};
