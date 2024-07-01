<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plans extends Model
{
    use HasFactory;

    const PREMIUM = 'PLP';
    const STANDARD = 'STD';
    const BASIC = 'BSC';

    protected $table = "plans";
    protected $primaryKey = "id";
    public $incrementing = false;
    protected $hidden = [
        'id','created_at'
    ];
}
