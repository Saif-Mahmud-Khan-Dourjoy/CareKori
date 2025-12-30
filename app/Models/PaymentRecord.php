<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRecord extends Model
{
    use HasFactory;


    protected $fillable = [
        'user_id',
        'trx_datetime',
        'trx_id',
        'amount_received_datetime',
        'sent_amount',
        'sent_datetime',
        'sent_via',
        'sent_trx_id',
        'sent_bank_acc',
        'amount_data',
    ];

    protected $casts = [
        'trx_datetime' => 'datetime',
        'amount_received_datetime' => 'datetime',
        'sent_datetime' => 'datetime',
        'amount_data' => 'array',
        'sent_amount' => 'decimal:2',
    ];
}
