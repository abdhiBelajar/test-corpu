<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modul extends Model
{
    protected $table = 'modul';
    protected $primaryKey = 'modul_id';
    
    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = null;
    
    protected $guarded = [];

    public function pembelajaran()
    {
        return $this->belongsTo(Pembelajaran::class, 'pembelajaran_id', 'pembelajaran_id');
    }

    public function materi()
    {
        return $this->hasMany(Materi::class, 'modul_id', 'modul_id');
    }

    public function materis() {
        return $this->hasMany(Materi::class, 'modul_id', 'modul_id')->orderBy('urutan');
    }

    public function kuis()
    {
        return $this->hasMany(Kuis::class, 'modul_id', 'modul_id');
    }

    public function progresModul()
    {
        return $this->hasMany(ProgresModul::class, 'modul_id', 'modul_id');
    }
}
