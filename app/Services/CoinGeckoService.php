<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class CoinGeckoService
{
    protected string $baseUrl;
    protected int $cacheTtl;
    protected int $timeout;
    protected int $retryTimes;
    protected int $retrySleep;

    /**
     * CoinGecko API coin IDs mapping.
     */
    protected const COIN_IDS = [
        'btc' => 'bitcoin',
        'eth' => 'ethereum',
        'usdt' => 'tether',
    ];

    public function __construct()
    {
        $this->baseUrl = config('services.coingecko.url', 'https://api.coingecko.com/api/v3');
        $this->cacheTtl = config('services.coingecko.cache_ttl', 120); // 2 minutes
        $this->timeout = config('services.coingecko.timeout', 10);
        $this->retryTimes = config('services.coingecko.retry_times', 3);
        $this->retrySleep = config('services.coingecko.retry_sleep', 1000);
    }

    /**
     * Get current exchange rates for all supported cryptocurrencies.
     *
     * @return array
     * @throws Exception
     */
    public function getRates(): array
    {
        $cacheKey = 'coingecko_rates';

        return Cache::remember($cacheKey, $this->cacheTtl, function () {
            try {
                $coinIds = implode(',', self::COIN_IDS);

                $response = Http::timeout($this->timeout)
                    ->retry($this->retryTimes, $this->retrySleep)
                    ->get("{$this->baseUrl}/simple/price", [
                        'ids' => $coinIds,
                        'vs_currencies' => 'ngn,usd',
                    ]);

                if (!$response->successful()) {
                    throw new Exception('CoinGecko API request failed: ' . $response->status());
                }

                $data = $response->json();

                return $this->formatRates($data);

            } catch (Exception $e) {
                Log::error('CoinGecko API Error', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Try to return cached data if API fails
                $cachedData = Cache::get($cacheKey . '_backup');
                if ($cachedData) {
                    Log::info('Returning backup cached CoinGecko rates due to API failure');
                    return $cachedData;
                }

                throw new Exception('Unable to fetch cryptocurrency rates. Please try again later.');
            }
        });
    }

    /**
     * Get rate for a specific cryptocurrency.
     *
     * @param string $cryptoType
     * @return array ['ngn' => float, 'usd' => float]
     * @throws Exception
     */
    public function getRate(string $cryptoType): array
    {
        $rates = $this->getRates();

        if (!isset($rates[$cryptoType])) {
            throw new Exception("Unsupported cryptocurrency: {$cryptoType}");
        }

        return $rates[$cryptoType];
    }

    /**
     * Get NGN rate for a specific cryptocurrency.
     *
     * @param string $cryptoType
     * @return float
     * @throws Exception
     */
    public function getNgnRate(string $cryptoType): float
    {
        $rate = $this->getRate($cryptoType);
        return $rate['ngn'];
    }


    public function calculateCryptoAmount(string $cryptoType, float $nairAmount): float
    {
        $rate = $this->getNgnRate($cryptoType);

        if ($rate <= 0) {
            throw new Exception('Invalid exchange rate');
        }

        return $nairAmount / $rate;
    }


    public function calculateNairaAmount(string $cryptoType, float $cryptoAmount): float
    {
        $rate = $this->getNgnRate($cryptoType);
        return $cryptoAmount * $rate;
    }


    protected function formatRates(array $rawData): array
    {
        $formatted = [];

        foreach (self::COIN_IDS as $symbol => $coinId) {
            if (isset($rawData[$coinId])) {
                $formatted[$symbol] = [
                    'ngn' => $rawData[$coinId]['ngn'] ?? 0,
                    'usd' => $rawData[$coinId]['usd'] ?? 0,
                ];
            }
        }

        Cache::put('coingecko_rates_backup', $formatted, now()->addHours(24));

        return $formatted;
    }


    public function clearCache(): void
    {
        Cache::forget('coingecko_rates');
    }

    public function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }

    public function hasCachedRates(): bool
    {
        return Cache::has('coingecko_rates');
    }
}
