<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cities extends Model
{
    use HasFactory;

    protected $table = "cities";
    protected $primaryKey = "id";
    public $incrementing = false;
    protected $hidden = [
        'id',
    ];
    public function cities()
    {
        return $this->belongsTo(Regions::class); 
    }
}
