<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kuis extends Model
{
    protected $table = 'kuis';
    protected $primaryKey = 'kuis_id';
    
    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = null;
    
    protected $guarded = [];

    public function modul()
    {
        return $this->belongsTo(Modul::class, 'modul_id', 'modul_id');
    }

    public function soalKuis()
    {
        return $this->hasMany(SoalKuis::class, 'kuis_id', 'kuis_id');
    }

    public function riwayatKuis()
    {
        return $this->hasMany(RiwayatKuis::class, 'kuis_id', 'kuis_id');
    }
}
