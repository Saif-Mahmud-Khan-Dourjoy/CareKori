<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasFactory;

    protected $table = 'refunds';

    
    protected $fillable = [
        'order_id', 
        'status', 
        'refund_amount', 
        'refund_ref_id', 
        'refund_remark', 
    ];

    // Define the relationship between Refund and Order (1-to-many)
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}