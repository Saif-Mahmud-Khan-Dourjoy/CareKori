<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentSlot extends Model
{
    use HasFactory;
    protected $fillable = ['availability_id', 'slot_time', 'is_booked'];

    public function availability()
    {
        return $this->belongsToMany(ServiceProviderAvailability::class, 'availability_id');
    }
}
