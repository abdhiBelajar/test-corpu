<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembelajaran', function (Blueprint $table) {
            $table->id('pembelajaran_id');
            $table->unsignedBigInteger('komunitas_id');
            $table->unsignedBigInteger('dirancang_oleh_pengguna_id');
            $table->string('judul_pembelajaran', 255);
            $table->text('deskripsi')->nullable();
            $table->string('kategori', 100)->nullable();
            $table->text('capaian_pembelajaran');
            $table->string('nama_narasumber', 100)->nullable();
            $table->decimal('nilai_kelulusan', 5, 2);
            $table->text('ringkasan_materi');
            $table->string('surat_pernyataan_url', 500)->nullable();
            $table->enum('status', ['draft', 'menunggu_approval', 'dipublikasikan'])->default('draft');
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->timestamp('dipublikasikan_pada')->nullable();
            $table->timestamp('dibuat_pada')->useCurrent();
            $table->timestamp('diperbarui_pada')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('komunitas_id')->references('komunitas_id')->on('komunitas')->onDelete('cascade');
            $table->foreign('dirancang_oleh_pengguna_id')->references('pengguna_id')->on('pengguna')->onDelete('cascade');
            
            $table->index(['komunitas_id', 'status']);
            $table->index(['tanggal_mulai', 'tanggal_selesai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembelajaran');
    }
};
