<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ulasan_pembelajaran', function (Blueprint $table) {
            $table->id('ulasan_id');
            $table->unsignedBigInteger('pendaftaran_id')->unique();
            $table->unsignedTinyInteger('skor_rating');
            $table->text('teks_ulasan')->nullable();
            $table->timestamp('dikirim_pada')->useCurrent();

            $table->foreign('pendaftaran_id')->references('pendaftaran_id')->on('pendaftaran_pembelajaran')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ulasan_pembelajaran');
    }
};
