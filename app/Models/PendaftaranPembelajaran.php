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

    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id', 'pengguna_id');
    }

    public function pembelajaran()
    {
        return $this->belongsTo(Pembelajaran::class, 'pembelajaran_id', 'pembelajaran_id');
    }

    public function progresModul()
    {
        return $this->hasMany(ProgresModul::class, 'pendaftaran_id', 'pendaftaran_id');
    }

    public function progresMateri()
    {
        return $this->hasMany(ProgresMateri::class, 'pendaftaran_id', 'pendaftaran_id');
    }
}
