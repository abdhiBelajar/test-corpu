<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validasi_pembelajaran', function (Blueprint $table) {
            $table->id('validasi_id');
            $table->unsignedBigInteger('pembelajaran_id');
            $table->unsignedBigInteger('divalidasi_oleh_pengguna_id');
            $table->enum('status_validasi', ['diajukan', 'disetujui', 'ditolak']);
            $table->text('catatan')->nullable();
            $table->timestamp('divalidasi_pada')->useCurrent();

            $table->foreign('pembelajaran_id')->references('pembelajaran_id')->on('pembelajaran')->onDelete('cascade');
            $table->foreign('divalidasi_oleh_pengguna_id')->references('pengguna_id')->on('pengguna')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validasi_pembelajaran');
    }
};
