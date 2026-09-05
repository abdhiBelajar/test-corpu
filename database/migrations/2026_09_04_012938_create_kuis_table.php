<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kuis', function (Blueprint $table) {
            $table->id('kuis_id');
            $table->unsignedBigInteger('modul_id')->unique();
            $table->string('judul_kuis', 200);
            $table->decimal('nilai_kelulusan', 5, 2);
            $table->unsignedInteger('maks_percobaan')->default(3);
            $table->boolean('acak_soal')->default(true);
            $table->boolean('tampilkan_kunci_setelah')->default(true);
            $table->timestamp('dibuat_pada')->useCurrent();

            $table->foreign('modul_id')->references('modul_id')->on('modul')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kuis');
    }
};
