<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendaftaranPembelajaran extends Model
{
    protected $table = 'pendaftaran_pembelajaran';
    protected $primaryKey = 'pendaftaran_id';
    
    const CREATED_AT = 'terdaftar_pada';
    const UPDATED_AT = null;
    
    protected $guarded = [];
}
