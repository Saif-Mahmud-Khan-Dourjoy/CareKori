<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LawyerSpeciality extends Model
{
    use HasFactory;
    protected $fillable = [
        'specialized_at','icon'
    ];
    public function lawyerProfiles()
    {
        return $this->hasMany(LawyerProfile::class, 'lawyer_speciality_id');
    }
}