<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_tiket', function (Blueprint $table) {
            $table->id('help_tiket_id');
            $table->unsignedBigInteger('pengguna_id');
            $table->string('subjek', 200);
            $table->text('deskripsi');
            $table->enum('status', ['terbuka', 'diproses', 'selesai'])->default('terbuka')->index();
            $table->unsignedBigInteger('ditangani_oleh_pengguna_id')->nullable();
            $table->timestamp('dibuat_pada')->useCurrent();
            $table->timestamp('diselesaikan_pada')->nullable();

            $table->foreign('pengguna_id')->references('pengguna_id')->on('pengguna')->onDelete('cascade');
            $table->foreign('ditangani_oleh_pengguna_id')->references('pengguna_id')->on('pengguna')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_tiket');
    }
};
