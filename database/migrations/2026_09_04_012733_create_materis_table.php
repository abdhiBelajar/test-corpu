<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materi', function (Blueprint $table) {
            $table->id('materi_id');
            $table->unsignedBigInteger('modul_id');
            $table->string('judul_materi', 255);
            $table->enum('tipe_materi', ['pdf', 'video_embed']);
            $table->string('tautan_atau_berkas', 500);
            $table->unsignedInteger('durasi_menit');
            $table->boolean('apakah_wajib')->default(true);
            $table->unsignedInteger('urutan');
            $table->timestamp('dibuat_pada')->useCurrent();

            $table->foreign('modul_id')->references('modul_id')->on('modul')->onDelete('cascade');
            $table->unique(['modul_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materi');
    }
};
