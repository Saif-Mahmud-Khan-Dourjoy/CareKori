<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LawyerSpeciality extends Model
{
    use HasFactory;
    protected $fillable = [
        'specialized_at',
    ];
}