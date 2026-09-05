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
        Schema::create('admin_komunitas', function (Blueprint $table) {
            $table->id('admin_komunitas_id');
            $table->unsignedBigInteger('komunitas_id');
            $table->unsignedBigInteger('pengguna_id');
            $table->timestamp('ditetapkan_pada')->useCurrent();

            $table->foreign('komunitas_id')->references('komunitas_id')->on('komunitas')->onDelete('cascade');
            $table->foreign('pengguna_id')->references('pengguna_id')->on('pengguna')->onDelete('cascade');
            $table->unique(['komunitas_id', 'pengguna_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_komunitas');
    }
};
