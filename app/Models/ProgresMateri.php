<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgresMateri extends Model
{
    protected $table = 'progres_materi';
    protected $primaryKey = 'progres_materi_id';
    
    const CREATED_AT = null;
    const UPDATED_AT = null; // No updated_at equivalent in schema, only diselesaikan_pada
    
    protected $guarded = [];

    public function materi()
    {
        return $this->belongsTo(Materi::class, 'materi_id');
    }

    public function pendaftaran()
    {
        return $this->belongsTo(PendaftaranPembelajaran::class, 'pendaftaran_id');
    }
}
