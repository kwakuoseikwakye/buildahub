<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceImage extends Model
{
    use HasFactory;
    protected $table = "services_images";
    protected $primaryKey = "id";
    public $incrementing = false;
    protected $fillable = [
        'image','services_id'
    ];
    protected $hidden = [
        'created_at','updated_at','id' 
    ];
}
