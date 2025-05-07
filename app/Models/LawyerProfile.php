<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LawyerProfile extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'lawyer_profiles';


    public function lawyerTitle()
    {
        return $this->belongsTo(LawyerTitle::class);
    }
}