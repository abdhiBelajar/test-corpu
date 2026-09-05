<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq', function (Blueprint $table) {
            $table->id('faq_id');
            $table->string('pertanyaan', 255);
            $table->text('jawaban');
            $table->string('kategori', 100)->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->unsignedBigInteger('dibuat_oleh_pengguna_id');
            $table->timestamp('dibuat_pada')->useCurrent();

            $table->foreign('dibuat_oleh_pengguna_id')->references('pengguna_id')->on('pengguna')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faq');
    }
};
