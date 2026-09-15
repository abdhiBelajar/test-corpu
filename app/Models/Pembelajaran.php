<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pembelajaran extends Model
{
    use SoftDeletes;
    protected $table = 'pembelajaran';
    protected $primaryKey = 'pembelajaran_id';
    
    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = 'diperbarui_pada';
    
    protected $guarded = [];

    protected $attributes = [
        'kategori' => 'Pengembangan Kompetensi',
    ];

    public function getKategoriAttribute($value)
    {
        return $value ?: 'Pengembangan Kompetensi';
    }

    public function komunitas()
    {
        return $this->belongsTo(Komunitas::class, 'komunitas_id', 'komunitas_id');
    }

    public function perancang()
    {
        return $this->belongsTo(Pengguna::class, 'dirancang_oleh_pengguna_id', 'pengguna_id');
    }

    public function modul()
    {
        return $this->hasMany(Modul::class, 'pembelajaran_id', 'pembelajaran_id');
    }

    public function moduls() {
        return $this->hasMany(Modul::class, 'pembelajaran_id', 'pembelajaran_id')->orderBy('urutan');
    }

    public function pembelajaranJp()
    {
        return $this->hasMany(PembelajaranJp::class, 'pembelajaran_id', 'pembelajaran_id');
    }

    public function postTest()
    {
        return $this->hasOne(PostTest::class, 'pembelajaran_id', 'pembelajaran_id');
    }

    public function postTests() {
        return $this->hasMany(PostTest::class, 'pembelajaran_id', 'pembelajaran_id');
    }

    public function validasi()
    {
        return $this->hasOne(ValidasiPembelajaran::class, 'pembelajaran_id', 'pembelajaran_id')->latestOfMany('divalidasi_pada');
    }

    public function pendaftaran()
    {
        return $this->hasMany(PendaftaranPembelajaran::class, 'pembelajaran_id', 'pembelajaran_id');
    }
}
