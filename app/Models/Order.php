<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'bank_tran_id',
        'refund_tran_id',
        'amount',
        'currency',
        'status',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'payment_verified_at',
    ];

    protected $casts = [
        'payment_verified_at' => 'datetime',
    ];

    public function refund()
    {
        return $this->hasOne(Refund::class);
    }
}