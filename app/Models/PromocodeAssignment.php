<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromocodeAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'promocode_id',
        'user_id',
        'role_id',
        'speciality_id',
        'speciality_type'
    ];

    public function promocode()
    {
        return $this->belongsTo(Promocode::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}