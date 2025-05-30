<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceProviderAvailability extends Model
{
    use HasFactory;
    protected $fillable = ['provider_id', 'availability_type', 'day', 'start_time', 'end_time', 'slot_duration'];

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function slots()
    {
        return $this->hasMany(AppointmentSlot::class, 'availability_id');
    }

    
}