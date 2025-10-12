<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerProfile extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'gender', 'dob', 'district', 'sub_district', 'union_name', 'active_status', 'avatar', 'address'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
