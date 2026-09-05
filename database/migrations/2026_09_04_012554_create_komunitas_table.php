<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('komunitas', function (Blueprint $table) {
            $table->id('komunitas_id');
            $table->unsignedBigInteger('dibuat_oleh_pengguna_id');
            $table->string('nama_komunitas', 100)->unique();
            $table->text('deskripsi')->nullable();
            $table->enum('rumpun_jabatan', ['JPT', 'JA', 'JF', 'Pelaksana'])->index();
            $table->json('sub_bidang_tersedia_json')->nullable();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamp('dibuat_pada')->useCurrent();
            $table->timestamp('diperbarui_pada')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('dibuat_oleh_pengguna_id')->references('pengguna_id')->on('pengguna')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('komunitas');
    }
};
