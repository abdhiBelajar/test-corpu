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
        Schema::create('pegawai_simpegs', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 30)->unique()->index();
            $table->string('nama_lengkap', 150);
            $table->string('jabatan', 150)->nullable();
            $table->enum('rumpun_jabatan', ['JPT', 'JA', 'JF', 'JP'])->default('JP')->index();
            $table->string('unit_kerja', 255)->nullable();
            $table->string('email', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawai_simpegs');
    }
};
