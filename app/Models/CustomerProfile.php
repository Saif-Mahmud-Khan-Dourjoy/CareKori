<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerProfile extends Model
{
    use HasFactory;
    protected $fillable = ['gender', 'dob', 'district', 'sub_district', 'union_name'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}