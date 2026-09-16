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
        Schema::table('komunitas', function (Blueprint $table) {
            if (!Schema::hasColumn('komunitas', 'thumbnail')) {
                $table->string('thumbnail', 500)->nullable()->after('deskripsi');
            }
        });

        Schema::table('modul', function (Blueprint $table) {
            if (!Schema::hasColumn('modul', 'thumbnail')) {
                $table->string('thumbnail', 500)->nullable()->after('gambaran_umum');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('komunitas', function (Blueprint $table) {
            if (Schema::hasColumn('komunitas', 'thumbnail')) {
                $table->dropColumn('thumbnail');
            }
        });

        Schema::table('modul', function (Blueprint $table) {
            if (Schema::hasColumn('modul', 'thumbnail')) {
                $table->dropColumn('thumbnail');
            }
        });
    }
};
