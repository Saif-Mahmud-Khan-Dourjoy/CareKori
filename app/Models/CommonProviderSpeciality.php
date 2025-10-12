<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommonProviderSpeciality extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'specialized_at',
        'description',
        'icon'
    ];

    public function category()
    {
        return $this->belongsTo(Role::class, 'category_id');
    }
    public function commonProfiles()
    {
        return $this->hasMany(CommonProfile::class, 'common_speciality_id');
    }
    
}