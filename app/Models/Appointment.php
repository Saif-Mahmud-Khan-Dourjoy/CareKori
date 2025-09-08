<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;
    protected $fillable = ['customer_id', 'provider_id', 'appointment_time', 'status', 'price', 'is_money_back', 'is_cancel_by_user', 'remarks'];

    protected $casts = [
        'appointment_time' => 'datetime',
    ];
    protected $appends = ['appointment_time_utc_iso'];

    // protected $hidden = ['appointment_time'];

    public function getAppointmentTimeUtcIsoAttribute()
    {
        return Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $this->getRawOriginal('appointment_time'),
            env('CUSTOMER_TIMEZONE', 'UTC')
        )
            ->toISOString();
    }

    public function getAppointmentTimeAttribute()
    {
        return Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $this->getRawOriginal('appointment_time'),
            env('CUSTOMER_TIMEZONE', 'UTC')
        )
            ->toISOString();
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }


    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }


    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }
}