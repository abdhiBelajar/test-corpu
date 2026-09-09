<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoalPostTest extends Model
{
    protected $table = 'soal_post_test';
    protected $primaryKey = 'soal_post_test_id';
    
    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = null;
    
    protected $guarded = [];

    protected $casts = [
        'pilihan_jawaban_json' => 'array',
    ];

    public function postTest()
    {
        return $this->belongsTo(PostTest::class, 'post_test_id', 'post_test_id');
    }
}
