<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AddBanner extends Model
{
    use HasFactory;
    protected $fillable = ['add_image', 'add_for', 'add_type', 'role_id'];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
