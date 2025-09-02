<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promocode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'discount',
        'discount_type',
        'valid_from',
        'valid_to',
        'is_active'
    ];

    public function assignments()
    {
        return $this->hasMany(PromocodeAssignment::class);
    }
}