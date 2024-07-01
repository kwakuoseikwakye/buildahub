<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdsImage extends Model
{
    use HasFactory;
    protected $table = "ads_images";
    protected $primaryKey = "id";
    public $incrementing = false;
    protected $fillable = [
        'image','ads_id'
    ];
    protected $hidden = [
        'created_at','updated_at','id' 
    ];
}
