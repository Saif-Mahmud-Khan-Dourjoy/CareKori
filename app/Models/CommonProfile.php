<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommonProfile extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'common_speciality_id', 'bio', 'pricing', 'availability', 'avatar', 'gender', 'dob', 'district', 'thana', 'identification_no', 'active_from', 'active_to', 'active_status', 'payment_type', 'payment_account', 'bank_name', 'account_title'];

    protected $table = 'common_profiles';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function uniqueIdentification()
    {
        return $this->hasOne(UniqueIdentification::class);
    }

    public function commonSpeciality()
    {
        return $this->belongsTo(CommonProviderSpeciality::class);
    }
}
