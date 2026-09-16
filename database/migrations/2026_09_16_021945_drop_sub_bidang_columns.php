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
        if (Schema::hasColumn('pengguna', 'sub_bidang_jf')) {
            Schema::table('pengguna', function (Blueprint $table) {
                $table->dropColumn('sub_bidang_jf');
            });
        }

        if (Schema::hasColumn('komunitas', 'sub_bidang_tersedia_json')) {
            Schema::table('komunitas', function (Blueprint $table) {
                $table->dropColumn('sub_bidang_tersedia_json');
            });
        }

        if (Schema::hasColumn('pendaftaran_pembelajaran', 'sub_bidang_dipilih')) {
            Schema::table('pendaftaran_pembelajaran', function (Blueprint $table) {
                $table->dropColumn('sub_bidang_dipilih');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('pengguna', 'sub_bidang_jf')) {
            Schema::table('pengguna', function (Blueprint $table) {
                $table->string('sub_bidang_jf', 100)->nullable()->after('rumpun_jabatan');
            });
        }

        if (!Schema::hasColumn('komunitas', 'sub_bidang_tersedia_json')) {
            Schema::table('komunitas', function (Blueprint $table) {
                $table->json('sub_bidang_tersedia_json')->nullable()->after('rumpun_jabatan');
            });
        }

        if (!Schema::hasColumn('pendaftaran_pembelajaran', 'sub_bidang_dipilih')) {
            Schema::table('pendaftaran_pembelajaran', function (Blueprint $table) {
                $table->string('sub_bidang_dipilih', 100)->nullable()->after('pengguna_id');
            });
        }
    }
};
