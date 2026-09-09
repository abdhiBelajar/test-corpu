<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembelajaranJp extends Model
{
    protected $table = 'pembelajaran_jp';
    protected $primaryKey = 'pembelajaran_jp_id';
    
    const CREATED_AT = 'diverifikasi_pada'; // Custom setup, since there is no default created_at
    const UPDATED_AT = null;
    
    public $timestamps = false; // Manually handle timestamps if needed
    
    protected $guarded = [];

    public function pembelajaran()
    {
        return $this->belongsTo(Pembelajaran::class, 'pembelajaran_id', 'pembelajaran_id');
    }
}
