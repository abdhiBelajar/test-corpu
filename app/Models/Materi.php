<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Materi extends Model
{
    protected $table = 'materi';
    protected $primaryKey = 'materi_id';
    
    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = null;
    
    protected $guarded = [];

    public function modul()
    {
        return $this->belongsTo(Modul::class, 'modul_id', 'modul_id');
    }

    public function progresMateri()
    {
        return $this->hasMany(ProgresMateri::class, 'materi_id', 'materi_id');
    }

    public function preTest()
    {
        return $this->hasOne(Kuis::class, 'materi_id', 'materi_id')->where('tipe_kuis', 'pre_test');
    }
}
