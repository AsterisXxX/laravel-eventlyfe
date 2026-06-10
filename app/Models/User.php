<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'username',
        'full_name',
        'email',
        'password',
        'role_id',
        'organizer_id',
        'bio',
        'organization_name',
        'profile_picture',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // =========================================
    // RELASI ELOQUENT
    // =========================================

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Cek apakah user memiliki role tertentu (helper)
    public function hasRole($slug)
    {
        return $this->role->slug === $slug;
    }

    public function events()
    {
        return $this->hasMany(Event::class, 'organizer_id');
    }

    public function assignedEvents()
    {
        return $this->belongsToMany(Event::class, 'event_checker', 'checker_id', 'event_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'user_id');
    }
}
