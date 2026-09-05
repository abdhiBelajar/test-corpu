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
        Schema::create('pengguna', function (Blueprint $table) {
            $table->id('pengguna_id');
            $table->string('nip', 20)->unique();
            $table->string('nama_lengkap', 100);
            $table->string('email', 100)->nullable()->unique();
            $table->string('kata_sandi_hash', 255);
            $table->enum('peran', ['admin_bkpsdm', 'admin_komunitas', 'peserta'])->default('peserta');
            $table->string('jabatan', 150)->nullable();
            $table->enum('rumpun_jabatan', ['JPT', 'JA', 'JF', 'Pelaksana'])->nullable()->index();
            $table->string('sub_bidang_jf', 100)->nullable();
            $table->string('unit_kerja', 150)->nullable();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->string('external_auth_id', 100)->nullable()->unique();
            $table->timestamp('dibuat_pada')->useCurrent();
            $table->timestamp('diperbarui_pada')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengguna');
    }
};
