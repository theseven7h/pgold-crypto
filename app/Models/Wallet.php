<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wallet extends Model
{
    use HasFactory, SoftDeletes;


    protected $fillable = [
        'user_id',
        'balance_kobo',
    ];


    protected function casts(): array
    {
        return [
            'balance_kobo' => 'integer',
        ];
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }


    public function getBalanceAttribute(): float
    {
        return $this->balance_kobo / 100;
    }

    public function getFormattedBalanceAttribute(): string
    {
        return '₦' . number_format($this->balance, 2);
    }

    public function setBalanceAttribute(float $value): void
    {
        $this->balance_kobo = (int) round($value * 100);
    }


    public function hasSufficientBalance(int $amountKobo): bool
    {
        return $this->balance_kobo >= $amountKobo;
    }


    public function credit(int $amountKobo): void
    {
        $this->increment('balance_kobo', $amountKobo);
    }

    public function debit(int $amountKobo): void
    {
        if (!$this->hasSufficientBalance($amountKobo)) {
            throw new \Exception('Insufficient wallet balance');
        }

        $this->decrement('balance_kobo', $amountKobo);
    }
}
