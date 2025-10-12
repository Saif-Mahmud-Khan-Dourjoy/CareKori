<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider_id',
        'complaint_text',
        'is_rude',
        'is_late',
        'interrupted',
        'appointment_id',
        'reported_by', 
    ];

    protected $casts = [
        'is_rude' => 'boolean',
        'is_late' => 'boolean',
        'interrupted' => 'boolean',
    ];

    // Relationship with the user who is submitting the complaint
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship with the provider being complained about
    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}