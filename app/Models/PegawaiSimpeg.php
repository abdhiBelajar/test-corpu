<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PegawaiSimpeg extends Model
{
    use HasFactory;

    protected $table = 'pegawai_simpegs';

    protected $fillable = [
        'nip',
        'nama_lengkap',
        'jabatan',
        'rumpun_jabatan',
        'unit_kerja',
        'email',
    ];
}
