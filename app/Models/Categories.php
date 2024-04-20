<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categories extends Model
{
    use HasFactory;
    protected $table = "categories";
    protected $primaryKey = "id";
    public $incrementing = false;
    // protected $hidden = [
    //     'id',
    // ];

    public function subCategories()
    {
        return $this->hasMany(SubCategories::class, 'category_id', 'id'); 
    }
}
