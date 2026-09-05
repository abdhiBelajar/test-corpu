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
}
