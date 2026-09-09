<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminKomunitas extends Model
{
    protected $table = 'admin_komunitas';
    protected $primaryKey = 'admin_komunitas_id';
    
    const CREATED_AT = 'ditetapkan_pada';
    const UPDATED_AT = null;
    
    protected $guarded = [];

    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id', 'pengguna_id');
    }

    public function komunitas()
    {
        return $this->belongsTo(Komunitas::class, 'komunitas_id', 'komunitas_id');
    }
}
