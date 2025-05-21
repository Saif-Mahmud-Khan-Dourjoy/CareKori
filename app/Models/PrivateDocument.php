<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrivateDocument extends Model
{
    use HasFactory;
    protected $fillable = ['document_id', 'created_by', 'created_for', 'appointment_id'];

    // Relationship with Document
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdFor()
    {
        return $this->belongsTo(User::class, 'created_for');
    }
}