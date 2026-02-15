<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CryptoTrade extends Model
{
    use HasFactory, SoftDeletes;


    public const CRYPTO_BTC = 'btc';
    public const CRYPTO_ETH = 'eth';
    public const CRYPTO_USDT = 'usdt';

    public const SUPPORTED_CRYPTOS = [
        self::CRYPTO_BTC,
        self::CRYPTO_ETH,
        self::CRYPTO_USDT,
    ];


    public const TRADE_TYPE_BUY = 'buy';
    public const TRADE_TYPE_SELL = 'sell';


    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';


    protected $fillable = [
        'transaction_id',
        'user_id',
        'crypto_type',
        'trade_type',
        'crypto_amount',
        'naira_amount_kobo',
        'rate_ngn',
        'fee_kobo',
        'fee_percentage',
        'status',
        'metadata',
    ];


    protected function casts(): array
    {
        return [
            'crypto_amount' => 'decimal:8',
            'naira_amount_kobo' => 'integer',
            'rate_ngn' => 'decimal:2',
            'fee_kobo' => 'integer',
            'fee_percentage' => 'decimal:2',
            'metadata' => 'array',
        ];
    }


    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    public function getNairaAmountAttribute(): float
    {
        return $this->naira_amount_kobo / 100;
    }


    public function getFeeAttribute(): float
    {
        return $this->fee_kobo / 100;
    }


    public function getFormattedNairaAmountAttribute(): string
    {
        return '₦' . number_format($this->naira_amount, 2);
    }


    public function getFormattedFeeAttribute(): string
    {
        return '₦' . number_format($this->fee, 2);
    }


    public function getCryptoNameAttribute(): string
    {
        return match($this->crypto_type) {
            self::CRYPTO_BTC => 'Bitcoin',
            self::CRYPTO_ETH => 'Ethereum',
            self::CRYPTO_USDT => 'Tether USD',
            default => strtoupper($this->crypto_type),
        };
    }


    public function scopeOfCrypto($query, string $cryptoType)
    {
        return $query->where('crypto_type', $cryptoType);
    }


    public function scopeOfTradeType($query, string $tradeType)
    {
        return $query->where('trade_type', $tradeType);
    }


    public function scopeBuys($query)
    {
        return $query->where('trade_type', self::TRADE_TYPE_BUY);
    }


    public function scopeSells($query)
    {
        return $query->where('trade_type', self::TRADE_TYPE_SELL);
    }


    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

   
    public static function isSupportedCrypto(string $cryptoType): bool
    {
        return in_array(strtolower($cryptoType), self::SUPPORTED_CRYPTOS);
    }
}
