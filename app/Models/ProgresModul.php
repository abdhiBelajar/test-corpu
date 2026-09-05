<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgresModul extends Model
{
    protected $table = 'progres_modul';
    protected $primaryKey = 'progres_modul_id';
    
    const CREATED_AT = null;
    const UPDATED_AT = 'diperbarui_pada';
    
    protected $guarded = [];
}
