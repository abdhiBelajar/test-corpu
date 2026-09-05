<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UlasanPembelajaran extends Model
{
    protected $table = 'ulasan_pembelajaran';
    protected $primaryKey = 'ulasan_id';
    
    const CREATED_AT = 'dikirim_pada';
    const UPDATED_AT = null;
    
    protected $guarded = [];
}
