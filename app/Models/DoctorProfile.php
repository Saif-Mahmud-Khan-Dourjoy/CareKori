<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorProfile extends Model
{
    use HasFactory;

    protected $guarded = [];
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
