<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pembelajaran', function (Blueprint $table) {
            $table->unsignedBigInteger('kategori_id')->nullable()->after('kategori');
            $table->foreign('kategori_id')
                ->references('kategori_id')
                ->on('kategori_kursus')
                ->onDelete('set null');
        });

        // Migrate existing courses from string 'kategori' to 'kategori_id'
        $categories = DB::table('kategori_kursus')->pluck('kategori_id', 'nama_kategori');
        $defaultKategoriId = $categories['Pengembangan Kompetensi'] ?? $categories->first();

        $courses = DB::table('pembelajaran')->select('pembelajaran_id', 'kategori')->get();
        foreach ($courses as $c) {
            $matchedId = null;
            if ($c->kategori && isset($categories[$c->kategori])) {
                $matchedId = $categories[$c->kategori];
            } else {
                $matchedId = $defaultKategoriId;
            }

            if ($matchedId) {
                DB::table('pembelajaran')
                    ->where('pembelajaran_id', $c->pembelajaran_id)
                    ->update(['kategori_id' => $matchedId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pembelajaran', function (Blueprint $table) {
            $table->dropForeign(['kategori_id']);
            $table->dropColumn('kategori_id');
        });
    }
};
