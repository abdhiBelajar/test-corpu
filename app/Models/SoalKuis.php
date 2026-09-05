<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoalKuis extends Model
{
    protected $table = 'soal_kuis';
    protected $primaryKey = 'soal_kuis_id';
    
    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = null;
    
    protected $guarded = [];
    
    protected $casts = [
        'pilihan_jawaban_json' => 'array',
    ];
}
