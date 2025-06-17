<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BannerCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function banner()
    {
        return $this->hasMany(AddBanner::class, 'category_id');
    }
}