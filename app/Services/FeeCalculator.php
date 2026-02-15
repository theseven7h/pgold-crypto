<?php

namespace App\Services;

use App\Models\CryptoTrade;

class FeeCalculator
{
    public function getBuyFeePercentage(): float
    {
        return (float) config('trading.fees.buy_percentage', 1.5);
    }


    public function getSellFeePercentage(): float
    {
        return (float) config('trading.fees.sell_percentage', 1.0);
    }


    public function calculateBuyFee(int $amountKobo): int
    {
        $feePercentage = $this->getBuyFeePercentage();
        return $this->calculateFee($amountKobo, $feePercentage);
    }


    public function calculateSellFee(int $amountKobo): int
    {
        $feePercentage = $this->getSellFeePercentage();
        return $this->calculateFee($amountKobo, $feePercentage);
    }


    protected function calculateFee(int $amountKobo, float $percentage): int
    {
        $fee = ($amountKobo * $percentage) / 100;
        return (int) round($fee);
    }

    public function calculateBuyTotal(int $amountKobo): array
    {
        $fee = $this->calculateBuyFee($amountKobo);
        $total = $amountKobo + $fee;

        return [
            'amount' => $amountKobo,
            'fee' => $fee,
            'total' => $total,
            'fee_percentage' => $this->getBuyFeePercentage(),
        ];
    }

    public function calculateSellNet(int $amountKobo): array
    {
        $fee = $this->calculateSellFee($amountKobo);
        $net = $amountKobo - $fee;

        return [
            'amount' => $amountKobo,
            'fee' => $fee,
            'net' => $net,
            'fee_percentage' => $this->getSellFeePercentage(),
        ];
    }


    public function getFeePercentageForTradeType(string $tradeType): float
    {
        return match($tradeType) {
            CryptoTrade::TRADE_TYPE_BUY => $this->getBuyFeePercentage(),
            CryptoTrade::TRADE_TYPE_SELL => $this->getSellFeePercentage(),
            default => 0.0,
        };
    }

    public function calculateFeeForTradeType(int $amountKobo, string $tradeType): int
    {
        return match($tradeType) {
            CryptoTrade::TRADE_TYPE_BUY => $this->calculateBuyFee($amountKobo),
            CryptoTrade::TRADE_TYPE_SELL => $this->calculateSellFee($amountKobo),
            default => 0,
        };
    }
}
