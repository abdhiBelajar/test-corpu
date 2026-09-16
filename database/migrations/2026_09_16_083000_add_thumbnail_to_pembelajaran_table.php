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
        Schema::table('pembelajaran', function (Blueprint $table) {
            if (!Schema::hasColumn('pembelajaran', 'thumbnail')) {
                $table->string('thumbnail', 500)->nullable()->after('deskripsi');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pembelajaran', function (Blueprint $table) {
            if (Schema::hasColumn('pembelajaran', 'thumbnail')) {
                $table->dropColumn('thumbnail');
            }
        });
    }
};
