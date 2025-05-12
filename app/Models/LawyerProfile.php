<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LawyerProfile extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'lawyer_title_id', 'bio', 'pricing', 'availability', 'avatar', 'gender', 'dob', 'district', 'thana', 'practice_area', 'identification_no', 'bar_registration_no', 'active_from', 'active_to', 'active_status'];
    protected $table = 'lawyer_profiles';


    public function lawyerTitle()
    {
        return $this->belongsTo(LawyerTitle::class);
    }
}