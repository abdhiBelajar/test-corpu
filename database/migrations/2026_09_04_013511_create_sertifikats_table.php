<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sertifikat', function (Blueprint $table) {
            $table->id('sertifikat_id');
            $table->unsignedBigInteger('pendaftaran_id')->unique();
            $table->string('nomor_sertifikat', 100)->unique();
            $table->string('nama_lengkap_snapshot', 100);
            $table->string('nip_snapshot', 20);
            $table->string('unit_kerja_snapshot', 150)->nullable();
            $table->date('tanggal_terbit');
            $table->string('tautan_berkas', 500);
            $table->enum('simpeg_sync_status', ['menunggu', 'tersinkron', 'gagal'])->default('menunggu');
            $table->enum('siasn_sync_status', ['menunggu', 'tersinkron', 'gagal', 'tidak_berlaku'])->nullable();
            $table->string('qr_signature_hash', 255)->nullable();
            $table->timestamp('dibuat_pada')->useCurrent();

            $table->foreign('pendaftaran_id')->references('pendaftaran_id')->on('pendaftaran_pembelajaran')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sertifikat');
    }
};
