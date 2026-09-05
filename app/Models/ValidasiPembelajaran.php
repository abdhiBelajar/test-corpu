<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ValidasiPembelajaran extends Model
{
    protected $table = 'validasi_pembelajaran';
    protected $primaryKey = 'validasi_id';
    
    const CREATED_AT = 'divalidasi_pada';
    const UPDATED_AT = null;
    
    protected $guarded = [];
}
