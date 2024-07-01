<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategories extends Model
{
    use HasFactory;

    protected $table = "sub_categories";
    protected $primaryKey = "id";
    public $incrementing = false;
    protected $hidden = [
        'category_id'
    ];
    public function categories()
    {
        return $this->belongsTo(Categories::class, 'category_id', 'id'); 
    }
}
