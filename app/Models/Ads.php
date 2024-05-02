<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ads extends Model
{
    use HasFactory;
    const CREATED_AT = "created_at";
    protected $table = "ads";
    protected $primaryKey = "id";
    public $incrementing = false;
    protected $fillable = [
        'model_id', 'user_id', 'item_name', 'category_id', 'city_id', 'condition_code', 'description', 'phone', 'plan_code'
    ];
    protected $hidden = [
        'created_at', 'updated_at', 'id'
    ];

    public function images()
    {
        return $this->hasMany(AdsImage::class, 'ads_id', 'model_id');
    }
    public function categories()
    {
        return $this->belongsTo(Categories::class, 'sub_category_id', 'id');
    }

    public function cities()
    {
        return $this->belongsTo(Cities::class, 'city_id', 'id');
    }

    public function conditions()
    {
        return $this->belongsTo(Condition::class, 'condition_code', 'condition_code');
    }

    public function plans()
    {
        return $this->belongsTo(Plans::class, 'plan_code', 'plan_code');
    }
}
