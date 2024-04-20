<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Regions extends Model
{
    use HasFactory;

    protected $table = "regions";
    protected $primaryKey = "id";
    public $incrementing = false;
    protected $hidden = [
        'id',
    ];

    public function cities()
    {
        return $this->hasMany(Cities::class, 'region_code', 'code'); 
    }
}
