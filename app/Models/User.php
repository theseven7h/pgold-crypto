<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;


    protected $fillable = [
        'name',
        'email',
        'password',
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


    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }


    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }


    public function cryptoTrades(): HasMany
    {
        return $this->hasMany(CryptoTrade::class);
    }


    protected static function boot()
    {
        parent::boot();

        static::created(function ($user) {
            $user->wallet()->create([
                'balance_kobo' => 0,
            ]);
        });
    }
}
