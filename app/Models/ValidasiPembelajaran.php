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

    public function pembelajaran()
    {
        return $this->belongsTo(Pembelajaran::class, 'pembelajaran_id', 'pembelajaran_id');
    }

    public function pemvalidasi()
    {
        return $this->belongsTo(Pengguna::class, 'divalidasi_oleh_pengguna_id', 'pengguna_id');
    }
}
