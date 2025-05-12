<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommonProfile extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'bio', 'pricing', 'availability', 'avatar', 'gender', 'dob', 'district', 'thana', 'identification_no', 'active_from', 'active_to', 'active_status'];

    protected $table = 'common_profiles';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function uniqueIdentification()
    {
        return $this->hasOne(UniqueIdentification::class);
    }
}