<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soal_kuis', function (Blueprint $table) {
            $table->id('soal_kuis_id');
            $table->unsignedBigInteger('kuis_id');
            $table->text('teks_soal');
            $table->json('pilihan_jawaban_json');
            $table->string('kunci_jawaban', 10);
            $table->decimal('bobot_nilai', 5, 2)->default(1);
            $table->boolean('terkunci')->default(false);
            $table->timestamp('dibuat_pada')->useCurrent();

            $table->foreign('kuis_id')->references('kuis_id')->on('kuis')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soal_kuis');
    }
};
