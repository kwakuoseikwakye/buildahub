<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArtisanServices extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = "services";
    protected $primaryKey = "id";
    public $incrementing = false;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'model_id', 'user_id', 'description', 'service_category_id', 'views',
        'description', 'phone', 'plan_code'
    ];
    
    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at', 'id'
    ];
}
