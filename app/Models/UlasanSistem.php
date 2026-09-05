<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UlasanSistem extends Model
{
    protected $table = 'ulasan_sistem';
    protected $primaryKey = 'ulasan_sistem_id';
    
    const CREATED_AT = 'dikirim_pada';
    const UPDATED_AT = null;
    
    protected $guarded = [];
}
