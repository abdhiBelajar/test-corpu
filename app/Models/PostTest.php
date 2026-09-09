<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostTest extends Model
{
    protected $table = 'post_test';
    protected $primaryKey = 'post_test_id';
    
    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = null;
    
    protected $guarded = [];

    public function pembelajaran()
    {
        return $this->belongsTo(Pembelajaran::class, 'pembelajaran_id', 'pembelajaran_id');
    }

    public function soalPostTest()
    {
        return $this->hasMany(SoalPostTest::class, 'post_test_id', 'post_test_id');
    }

    public function riwayatPostTest()
    {
        return $this->hasMany(RiwayatPostTest::class, 'post_test_id', 'post_test_id');
    }
}
