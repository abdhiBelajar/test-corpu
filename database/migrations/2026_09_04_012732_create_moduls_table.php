<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modul', function (Blueprint $table) {
            $table->id('modul_id');
            $table->unsignedBigInteger('pembelajaran_id');
            $table->string('judul_modul', 255);
            $table->text('gambaran_umum');
            $table->text('evaluasi_deskripsi')->nullable();
            $table->unsignedInteger('urutan');
            $table->unsignedInteger('durasi_total_menit')->default(0);
            $table->decimal('jp_modul', 3, 2); 
            $table->text('info_tatap_muka')->nullable();
            $table->timestamp('dibuat_pada')->useCurrent();

            $table->foreign('pembelajaran_id')->references('pembelajaran_id')->on('pembelajaran')->onDelete('cascade');
            $table->unique(['pembelajaran_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modul');
    }
};
