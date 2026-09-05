<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ulasan_sistem', function (Blueprint $table) {
            $table->id('ulasan_sistem_id');
            $table->unsignedBigInteger('pengguna_id');
            $table->unsignedTinyInteger('skor_rating');
            $table->string('kategori', 100)->nullable();
            $table->text('catatan_keluhan')->nullable();
            $table->timestamp('dikirim_pada')->useCurrent();

            $table->foreign('pengguna_id')->references('pengguna_id')->on('pengguna')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ulasan_sistem');
    }
};
