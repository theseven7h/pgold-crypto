<?php

namespace App\Services;

use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InvalidTradeException;
use App\Models\CryptoTrade;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CryptoTradeService
{
    public function __construct(
        protected CoinGeckoService $coinGeckoService,
        protected WalletService $walletService,
        protected FeeCalculator $feeCalculator
    ) {}

    /**
     * Execute a buy trade (user buys crypto with Naira).
     *
     * @param User $user
     * @param string $cryptoType
     * @param float $nairaAmount Amount in Naira to spend
     * @return CryptoTrade
     * @throws InsufficientFundsException|InvalidTradeException
     */
    public function buy(User $user, string $cryptoType, float $nairaAmount): CryptoTrade
    {
        // Validate crypto type
        if (!CryptoTrade::isSupportedCrypto($cryptoType)) {
            throw new InvalidTradeException("Unsupported cryptocurrency: {$cryptoType}");
        }

        // Validate minimum transaction amount
        $minimumAmount = config('trading.minimum_transaction_amount', 100000) / 100; // Convert from kobo
        if ($nairaAmount < $minimumAmount) {
            throw new InvalidTradeException(
                "Minimum transaction amount is ₦" . number_format($minimumAmount, 2)
            );
        }

        return DB::transaction(function () use ($user, $cryptoType, $nairaAmount) {
            // Lock user's wallet
            $wallet = $user->wallet()->lockForUpdate()->first();

            // Get current rate from CoinGecko
            $rate = $this->coinGeckoService->getNgnRate($cryptoType);

            // Convert amounts to kobo for precision
            $nairaAmountKobo = (int) round($nairaAmount * 100);

            // Calculate fee
            $feeCalculation = $this->feeCalculator->calculateBuyTotal($nairaAmountKobo);
            $feeKobo = $feeCalculation['fee'];
            $totalChargeKobo = $feeCalculation['total'];

            // Check sufficient balance (amount + fee)
            if (!$wallet->hasSufficientBalance($totalChargeKobo)) {
                throw new InsufficientFundsException(
                    "Insufficient balance. Required: ₦" . number_format($totalChargeKobo / 100, 2) .
                    " (including ₦" . number_format($feeKobo / 100, 2) . " fee), " .
                    "Available: ₦" . number_format($wallet->balance, 2)
                );
            }

            // Calculate crypto amount
            $cryptoAmount = $nairaAmount / $rate;

            // Debit wallet (amount + fee)
            $wallet->debit($totalChargeKobo);
            $wallet->save();

            // Create transaction record
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => Transaction::TYPE_CRYPTO_TRADE,
                'amount_kobo' => -$totalChargeKobo, // Negative for debit
                'status' => Transaction::STATUS_COMPLETED,
                'balance_after' => $wallet->balance_kobo,
                'metadata' => [
                    'trade_type' => 'buy',
                    'crypto_type' => $cryptoType,
                    'crypto_amount' => $cryptoAmount,
                    'rate' => $rate,
                    'fee' => $feeKobo / 100,
                ],
            ]);

            // Create crypto trade record
            $trade = CryptoTrade::create([
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'crypto_type' => $cryptoType,
                'trade_type' => CryptoTrade::TRADE_TYPE_BUY,
                'crypto_amount' => $cryptoAmount,
                'naira_amount_kobo' => $nairaAmountKobo,
                'rate_ngn' => $rate,
                'fee_kobo' => $feeKobo,
                'fee_percentage' => $feeCalculation['fee_percentage'],
                'status' => CryptoTrade::STATUS_COMPLETED,
                'metadata' => [
                    'total_charged_kobo' => $totalChargeKobo,
                ],
            ]);

            Log::info('Crypto buy trade executed', [
                'user_id' => $user->id,
                'crypto_type' => $cryptoType,
                'crypto_amount' => $cryptoAmount,
                'naira_amount' => $nairaAmount,
                'fee' => $feeKobo / 100,
                'rate' => $rate,
                'trade_id' => $trade->id,
            ]);

            return $trade->load('transaction');
        });
    }

    /**
     * Execute a sell trade (user sells crypto for Naira).
     *
     * @param User $user
     * @param string $cryptoType
     * @param float $cryptoAmount Amount of crypto to sell
     * @return CryptoTrade
     * @throws InvalidTradeException
     */
    public function sell(User $user, string $cryptoType, float $cryptoAmount): CryptoTrade
    {
        // Validate crypto type
        if (!CryptoTrade::isSupportedCrypto($cryptoType)) {
            throw new InvalidTradeException("Unsupported cryptocurrency: {$cryptoType}");
        }

        // Validate crypto amount
        if ($cryptoAmount <= 0) {
            throw new InvalidTradeException("Crypto amount must be greater than zero");
        }

        return DB::transaction(function () use ($user, $cryptoType, $cryptoAmount) {
            // Lock user's wallet
            $wallet = $user->wallet()->lockForUpdate()->first();

            // Get current rate from CoinGecko
            $rate = $this->coinGeckoService->getNgnRate($cryptoType);

            // Calculate Naira value
            $nairaValue = $cryptoAmount * $rate;
            $nairaValueKobo = (int) round($nairaValue * 100);

            // Validate minimum transaction amount
            $minimumAmountKobo = config('trading.minimum_transaction_amount', 100000);
            if ($nairaValueKobo < $minimumAmountKobo) {
                throw new InvalidTradeException(
                    "Transaction value below minimum of ₦" . number_format($minimumAmountKobo / 100, 2)
                );
            }

            // Calculate fee
            $feeCalculation = $this->feeCalculator->calculateSellNet($nairaValueKobo);
            $feeKobo = $feeCalculation['fee'];
            $netAmountKobo = $feeCalculation['net'];

            // Credit wallet (naira value - fee)
            $wallet->credit($netAmountKobo);
            $wallet->save();

            // Create transaction record
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => Transaction::TYPE_CRYPTO_TRADE,
                'amount_kobo' => $netAmountKobo, // Positive for credit
                'status' => Transaction::STATUS_COMPLETED,
                'balance_after' => $wallet->balance_kobo,
                'metadata' => [
                    'trade_type' => 'sell',
                    'crypto_type' => $cryptoType,
                    'crypto_amount' => $cryptoAmount,
                    'rate' => $rate,
                    'fee' => $feeKobo / 100,
                ],
            ]);

            // Create crypto trade record
            $trade = CryptoTrade::create([
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'crypto_type' => $cryptoType,
                'trade_type' => CryptoTrade::TRADE_TYPE_SELL,
                'crypto_amount' => $cryptoAmount,
                'naira_amount_kobo' => $nairaValueKobo,
                'rate_ngn' => $rate,
                'fee_kobo' => $feeKobo,
                'fee_percentage' => $feeCalculation['fee_percentage'],
                'status' => CryptoTrade::STATUS_COMPLETED,
                'metadata' => [
                    'gross_naira_value_kobo' => $nairaValueKobo,
                    'net_amount_received_kobo' => $netAmountKobo,
                ],
            ]);

            Log::info('Crypto sell trade executed', [
                'user_id' => $user->id,
                'crypto_type' => $cryptoType,
                'crypto_amount' => $cryptoAmount,
                'naira_value' => $nairaValue,
                'fee' => $feeKobo / 100,
                'net_received' => $netAmountKobo / 100,
                'rate' => $rate,
                'trade_id' => $trade->id,
            ]);

            return $trade->load('transaction');
        });
    }

    /**
     * Get user's crypto trade history.
     *
     * @param User $user
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getTradeHistory(User $user, array $filters = [], int $perPage = 20)
    {
        $query = $user->cryptoTrades()->with('transaction');

        // Apply filters
        if (!empty($filters['crypto_type'])) {
            $query->where('crypto_type', $filters['crypto_type']);
        }

        if (!empty($filters['trade_type'])) {
            $query->where('trade_type', $filters['trade_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        return $query->latest()->paginate($perPage);
    }


    public function calculateBuyPreview(string $cryptoType, float $nairaAmount): array
    {
        $rate = $this->coinGeckoService->getNgnRate($cryptoType);
        $nairaAmountKobo = (int) round($nairaAmount * 100);

        $feeCalculation = $this->feeCalculator->calculateBuyTotal($nairaAmountKobo);
        $cryptoAmount = $nairaAmount / $rate;

        return [
            'crypto_type' => $cryptoType,
            'crypto_amount' => $cryptoAmount,
            'naira_cost' => $nairaAmount,
            'fee' => $feeCalculation['fee'] / 100,
            'total_charge' => $feeCalculation['total'] / 100,
            'rate' => $rate,
            'fee_percentage' => $feeCalculation['fee_percentage'],
        ];
    }


    public function calculateSellPreview(string $cryptoType, float $cryptoAmount): array
    {
        $rate = $this->coinGeckoService->getNgnRate($cryptoType);
        $nairaValue = $cryptoAmount * $rate;
        $nairaValueKobo = (int) round($nairaValue * 100);

        $feeCalculation = $this->feeCalculator->calculateSellNet($nairaValueKobo);

        return [
            'crypto_type' => $cryptoType,
            'crypto_amount' => $cryptoAmount,
            'gross_naira_value' => $nairaValue,
            'fee' => $feeCalculation['fee'] / 100,
            'net_received' => $feeCalculation['net'] / 100,
            'rate' => $rate,
            'fee_percentage' => $feeCalculation['fee_percentage'],
        ];
    }
}
