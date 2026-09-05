<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpTiket extends Model
{
    protected $table = 'help_tiket';
    protected $primaryKey = 'help_tiket_id';
    
    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = null;
    
    protected $guarded = [];
}
