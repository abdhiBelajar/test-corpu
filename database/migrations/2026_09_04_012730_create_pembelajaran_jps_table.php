<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembelajaran_jp', function (Blueprint $table) {
            $table->id('pembelajaran_jp_id');
            $table->unsignedBigInteger('pembelajaran_id')->unique();
            $table->enum('jenis_pelatihan', ['formal', 'bimtek', 'coaching', 'mentoring']);
            $table->unsignedInteger('durasi_menit');
            $table->decimal('jp_dihitung_sistem', 4, 2);
            $table->decimal('jp_final', 4, 2)->nullable();
            $table->boolean('diverifikasi_oleh_bkpsdm')->default(false);
            $table->timestamp('diverifikasi_pada')->nullable();

            $table->foreign('pembelajaran_id')->references('pembelajaran_id')->on('pembelajaran')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembelajaran_jp');
    }
};
