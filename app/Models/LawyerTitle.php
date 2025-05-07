<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LawyerTitle extends Model
{
    use HasFactory;

    protected $table = 'lawyer_titles';
    protected $fillable = [
        'title',
    ];
    
    public function lawyerProfiles()
    {
        return $this->hasMany(LawyerProfile::class, 'lawyer_title_id');
    }

  
}