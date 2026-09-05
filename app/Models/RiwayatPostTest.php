<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatPostTest extends Model
{
    protected $table = 'riwayat_post_test';
    protected $primaryKey = 'riwayat_post_test_id';
    
    const CREATED_AT = 'dikirim_pada';
    const UPDATED_AT = null;
    
    protected $guarded = [];

    protected $casts = [
        'jawaban_peserta_json' => 'array',
        'snapshot_soal_json' => 'array',
    ];
}
