<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progres_modul', function (Blueprint $table) {
            $table->id('progres_modul_id');
            $table->unsignedBigInteger('pendaftaran_id');
            $table->unsignedBigInteger('modul_id');
            $table->enum('status', ['belum_mulai', 'sedang_berjalan', 'selesai'])->default('belum_mulai');
            $table->timestamp('diperbarui_pada')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('pendaftaran_id')->references('pendaftaran_id')->on('pendaftaran_pembelajaran')->onDelete('cascade');
            $table->foreign('modul_id')->references('modul_id')->on('modul')->onDelete('cascade');
            $table->unique(['pendaftaran_id', 'modul_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progres_modul');
    }
};
