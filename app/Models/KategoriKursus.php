<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KategoriKursus extends Model
{
    use SoftDeletes;

    protected $table = 'kategori_kursus';
    protected $primaryKey = 'kategori_id';

    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = 'diperbarui_pada';

    protected $guarded = [];

    public function pembelajaran()
    {
        return $this->hasMany(Pembelajaran::class, 'kategori_id', 'kategori_id');
    }
}
