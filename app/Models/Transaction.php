<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Transaction types
     */
    public const TYPE_WALLET_DEPOSIT = 'wallet_deposit';
    public const TYPE_WALLET_WITHDRAWAL = 'wallet_withdrawal';
    public const TYPE_CRYPTO_TRADE = 'crypto_trade';

    /**
     * Transaction statuses
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'wallet_id',
        'type',
        'amount_kobo',
        'status',
        'metadata',
        'reference',
        'balance_after',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_kobo' => 'integer',
            'balance_after' => 'integer',
            'metadata' => 'array',
        ];
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }


    public function cryptoTrade(): HasOne
    {
        return $this->hasOne(CryptoTrade::class);
    }


    public function getAmountAttribute(): float
    {
        return $this->amount_kobo / 100;
    }


    public function getFormattedAmountAttribute(): string
    {
        return '₦' . number_format($this->amount, 2);
    }


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->reference)) {
                $transaction->reference = static::generateReference();
            }
        });
    }


    public static function generateReference(): string
    {
        do {
            $reference = 'TXN-' . strtoupper(Str::random(12));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }


    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }
}
