<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name','identification_placeholder'];
    public function users()
    {
        return $this->hasMany(User::class);
    }
    
    public function commonProviderSpecialities()
    {
        return $this->hasMany(CommonProviderSpeciality::class, 'category_id');
    }
}