<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatKuis extends Model
{
    protected $table = 'riwayat_kuis';
    protected $primaryKey = 'riwayat_kuis_id';
    
    const CREATED_AT = 'dikerjakan_pada';
    const UPDATED_AT = null;
    
    protected $guarded = [];

    protected $casts = [
        'jawaban_peserta_json' => 'array',
        'snapshot_soal_json' => 'array',
    ];

    public function kuis()
    {
        return $this->belongsTo(Kuis::class, 'kuis_id');
    }

    public function pendaftaran()
    {
        return $this->belongsTo(PendaftaranPembelajaran::class, 'pendaftaran_id');
    }
}
