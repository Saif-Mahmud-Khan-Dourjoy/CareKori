<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorSpeciality extends Model
{
    use HasFactory;

    protected $fillable = [
        'specialized_at',
    ];

    public function doctorProfiles()
    {
        return $this->hasMany(DoctorProfile::class, 'doctor_speciality_id');
    }
}