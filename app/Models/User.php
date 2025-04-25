<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; // Use Authenticatable for user authentication
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
     use HasApiTokens, HasFactory, Notifiable; // Include Notifiable for notifications

    // Specify which attributes can be mass assigned
    protected $fillable = [
        'name',
        'email',
        'image',
        'password',
    ];

    // Optionally, you can also define hidden attributes
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // You can also define any other model methods or relationships here
}
