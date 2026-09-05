<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_test', function (Blueprint $table) {
            $table->id('post_test_id');
            $table->unsignedBigInteger('pembelajaran_id')->unique();
            $table->decimal('nilai_kelulusan', 5, 2);
            $table->unsignedInteger('maks_percobaan')->default(3);
            $table->boolean('acak_soal')->default(true);
            $table->boolean('tampilkan_kunci_setelah')->default(true);
            $table->unsignedInteger('durasi_menit')->nullable();
            $table->timestamp('dibuat_pada')->useCurrent();

            $table->foreign('pembelajaran_id')->references('pembelajaran_id')->on('pembelajaran')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_test');
    }
};
