<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reviews extends Model
{
    use HasFactory;
    protected $table = "reviews";
    protected $primaryKey = "id";
    public $incrementing = false;
    protected $fillable = [
        'ads_id', 'user_id', 'message', 'rating'
    ];

    protected $hidden = [
        'id'
    ];
}
