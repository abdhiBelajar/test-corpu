<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Komunitas extends Model
{
    use SoftDeletes;
    protected $table = 'komunitas';
    protected $primaryKey = 'komunitas_id';
    
    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = 'diperbarui_pada';
    
    protected $guarded = [];


    public function pembuat()
    {
        return $this->belongsTo(Pengguna::class, 'dibuat_oleh_pengguna_id', 'pengguna_id');
    }

    public function pembelajaran()
    {
        return $this->hasMany(Pembelajaran::class, 'komunitas_id', 'komunitas_id');
    }

    public function adminKomunitas()
    {
        return $this->hasMany(AdminKomunitas::class, 'komunitas_id', 'komunitas_id');
    }

    public function anggota()
    {
        return $this->belongsToMany(Pengguna::class, 'komunitas_pengguna', 'komunitas_id', 'pengguna_id')
                    ->withPivot('bergabung_pada');
    }
}
