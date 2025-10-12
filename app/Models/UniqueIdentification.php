<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UniqueIdentification extends Model
{
    use HasFactory;

    protected $fillable = ['common_profile_id', 'unique_identification_no', 'other_data'];
    protected $table = 'unique_identifications';
    
    public function commonProfile()
    {
        return $this->belongsTo(CommonProfile::class);
    }
    
}