<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderWithdrawal extends Model
{
    use HasFactory;

    protected $table = 'provider_withdrawals';
    protected $guarded = [];
}
