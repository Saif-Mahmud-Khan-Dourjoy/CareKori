<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'role_id',
        'name',
        'email',
        'phone',
        'password',
        'unique_user_id', // Unique user ID for each user
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // 'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function customerProfile()
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function moderatorProfile()
    {
        return $this->hasOne(ModeratorProfile::class);
    }

    public function doctorProfile()
    {
        return $this->hasOne(DoctorProfile::class);
    }

    public function lawyerProfile()
    {
        return $this->hasOne(LawyerProfile::class);
    }

    public function commonProfile()
    {
        return $this->hasOne(CommonProfile::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);  // A user can have one wallet
    }

    public function appointmentsAsCustomer()
    {
        return $this->hasMany(Appointment::class, 'customer_id');
    }

    public function appointmentsAsProvider()
    {
        return $this->hasMany(Appointment::class, 'provider_id');
    }

    public function availability()
    {
        return $this->hasMany(ServiceProviderAvailability::class, 'provider_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    // A user can have many private documents (if they're the one who created it or created for)
    public function privateDocumentsCreated()
    {
        return $this->hasMany(PrivateDocument::class, 'created_by');
    }

    public function privateDocumentsReceived()
    {
        return $this->hasMany(PrivateDocument::class, 'created_for');
    }

    public function givenReviews()
    {
        return $this->hasMany(Review::class, 'customer_id');
    }

    public function receivedReviews()
    {
        return $this->hasMany(Review::class, 'service_provider_id');
    }

    public function languageState()
    {
        return $this->hasOne(LanguageState::class);  // A user can have one wallet
    }

    public function hasRole($role)
    {
        return $this->role && Str::lower($this->role->name) === $role;
    }

    public static function findByUniqueUserId($uniqueUserId)
    {
        return self::where('unique_user_id', $uniqueUserId)->first();
    }

    public function averageRating()
    {
          $avg_rating= $this->receivedReviews()
            ->where('status', 'approved')
            ->avg('rating');

        return $avg_rating ? round($avg_rating, 2) : 0; // Return average rating rounded to 2 decimal places, or 0 if no ratings
    }

    public function reviewCount()
    {
        return $this->receivedReviews()
            ->where('status', 'approved')
            ->count();
    }

    
}