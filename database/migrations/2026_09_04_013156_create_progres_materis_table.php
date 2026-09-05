<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progres_materi', function (Blueprint $table) {
            $table->id('progres_materi_id');
            $table->unsignedBigInteger('pendaftaran_id');
            $table->unsignedBigInteger('materi_id');
            $table->boolean('apakah_selesai')->default(false);
            $table->timestamp('diselesaikan_pada')->nullable();

            $table->foreign('pendaftaran_id')->references('pendaftaran_id')->on('pendaftaran_pembelajaran')->onDelete('cascade');
            $table->foreign('materi_id')->references('materi_id')->on('materi')->onDelete('cascade');
            $table->unique(['pendaftaran_id', 'materi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progres_materi');
    }
};
