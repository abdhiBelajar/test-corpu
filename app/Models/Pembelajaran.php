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

    protected $appends = ['thumbnail_url'];

    public function getThumbnailUrlAttribute()
    {
        if (!empty($this->attributes['thumbnail'])) {
            if (filter_var($this->attributes['thumbnail'], FILTER_VALIDATE_URL)) {
                return $this->attributes['thumbnail'];
            }
            $clean = ltrim($this->attributes['thumbnail'], '/');
            if (str_starts_with($clean, 'storage/')) {
                $clean = substr($clean, 8);
            }
            return asset('storage/' . $clean);
        }

        // Fallback: jika cover kursus belum diatur, gunakan thumbnail modul yang ada
        try {
            $modulWithThumb = $this->moduls()->whereNotNull('thumbnail')->where('thumbnail', '!=', '')->first();
            if ($modulWithThumb && !empty($modulWithThumb->thumbnail_url)) {
                return $modulWithThumb->thumbnail_url;
            }
        } catch (\Throwable $e) {
            // ignore fallback error if relation fails
        }

        return 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=2070&auto=format&fit=crop';
    }

    protected $attributes = [
        'kategori' => 'Pengembangan Kompetensi',
    ];

    public function getKategoriAttribute($value)
    {
        if ($this->relationLoaded('kategoriKursus') && $this->kategoriKursus) {
            return $this->kategoriKursus->nama_kategori;
        }
        return $value ?: 'Pengembangan Kompetensi';
    }

    public function kategoriKursus()
    {
        return $this->belongsTo(KategoriKursus::class, 'kategori_id', 'kategori_id');
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
