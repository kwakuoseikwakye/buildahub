<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    const CREATED_AT = "created_at";
    const UPDATED_AT = "updated_at";

    protected $table = "payments";
    protected $primaryKey = "id";
    public $incrementing = false;

    const SUCCESS = 'success';
    const FAILED = 'failed';
    const PENDING = 'pending';

    protected $fillable = [
        "transaction_id", "amount_paid", "order_id",
        "userid", "status", "payment_mode",
    ];
}
