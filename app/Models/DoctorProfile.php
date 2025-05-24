<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorProfile extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'doctor_type_id', 'doctor_speciality_id', 'doctor_title_id', 'bio', 'pricing', 'availability', 'avatar', 'gender', 'dob', 'district', 'thana', 'identification_no', 'registration_no', 'active_from', 'active_to', 'active_status','payment_type', 'payment_account'];

    protected $table = 'doctor_profiles';

    public function doctorType()
    {
        return $this->belongsTo(DoctorType::class);
    }
    public function doctorSpeciality()
    {
        return $this->belongsTo(DoctorSpeciality::class);
    }
    public function doctorTitle()
    {
        return $this->belongsTo(DoctorTitle::class);
    }
}