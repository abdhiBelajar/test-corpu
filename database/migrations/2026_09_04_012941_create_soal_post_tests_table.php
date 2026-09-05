<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soal_post_test', function (Blueprint $table) {
            $table->id('soal_post_test_id');
            $table->unsignedBigInteger('post_test_id');
            $table->text('teks_soal');
            $table->json('pilihan_jawaban_json');
            $table->string('kunci_jawaban', 10);
            $table->decimal('bobot_nilai', 5, 2)->default(1);
            $table->boolean('terkunci')->default(false);
            $table->timestamp('dibuat_pada')->useCurrent();

            $table->foreign('post_test_id')->references('post_test_id')->on('post_test')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soal_post_test');
    }
};
