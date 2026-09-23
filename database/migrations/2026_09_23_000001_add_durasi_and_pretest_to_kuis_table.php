<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kuis', function (Blueprint $table) {
            // Drop foreign key and unique constraint on modul_id
            $table->dropForeign(['modul_id']);
            $table->dropUnique(['modul_id']);
            // Re-add foreign key as non-unique
            $table->foreign('modul_id')->references('modul_id')->on('modul')->onDelete('cascade');

            // Tambah durasi pengerjaan kuis (menit)
            $table->unsignedInteger('durasi_menit')->nullable()->after('tampilkan_kunci_setelah');

            // Tambah tipe kuis: evaluasi_modul (default) atau pre_test
            $table->string('tipe_kuis', 50)->default('evaluasi_modul')->after('durasi_menit');

            // Tambah relasi materi_id untuk pre_test
            $table->unsignedBigInteger('materi_id')->nullable()->after('tipe_kuis');
            $table->foreign('materi_id')->references('materi_id')->on('materi')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('kuis', function (Blueprint $table) {
            $table->dropForeign(['materi_id']);
            $table->dropColumn(['materi_id', 'tipe_kuis', 'durasi_menit']);

            $table->dropForeign(['modul_id']);
            $table->unique('modul_id');
            $table->foreign('modul_id')->references('modul_id')->on('modul')->onDelete('cascade');
        });
    }
};
