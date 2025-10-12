<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'document_link', 'type'];

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function privateDocument()
    {
        return $this->hasOne(PrivateDocument::class);
    }
}