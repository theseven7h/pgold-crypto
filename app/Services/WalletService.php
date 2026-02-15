<?php

namespace App\Services;

use App\Exceptions\InsufficientFundsException;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletService
{

    public function getWallet(User $user): Wallet
    {
        return $user->wallet;
    }

    public function getBalance(User $user): float
    {
        return $user->wallet->balance;
    }

    public function credit(User $user, int $amountKobo, string $notes = null): Transaction
    {
        return DB::transaction(function () use ($user, $amountKobo, $notes) {
            $wallet = $user->wallet()->lockForUpdate()->first();

            $wallet->credit($amountKobo);
            $wallet->save();

            $transaction = $this->createTransaction(
                user: $user,
                wallet: $wallet,
                type: Transaction::TYPE_WALLET_DEPOSIT,
                amountKobo: $amountKobo,
                balanceAfter: $wallet->balance_kobo,
                notes: $notes
            );

            Log::info('Wallet credited', [
                'user_id' => $user->id,
                'amount_kobo' => $amountKobo,
                'balance_after' => $wallet->balance_kobo,
                'transaction_id' => $transaction->id,
            ]);

            return $transaction;
        });
    }


    public function debit(User $user, int $amountKobo, string $notes = null): Transaction
    {
        return DB::transaction(function () use ($user, $amountKobo, $notes) {
            $wallet = $user->wallet()->lockForUpdate()->first();

            if (!$wallet->hasSufficientBalance($amountKobo)) {
                throw new InsufficientFundsException(
                    "Insufficient wallet balance. Required: ₦" . number_format($amountKobo / 100, 2) .
                    ", Available: ₦" . number_format($wallet->balance, 2)
                );
            }

            $wallet->debit($amountKobo);
            $wallet->save();

            $transaction = $this->createTransaction(
                user: $user,
                wallet: $wallet,
                type: Transaction::TYPE_WALLET_WITHDRAWAL,
                amountKobo: -$amountKobo,
                balanceAfter: $wallet->balance_kobo,
                notes: $notes
            );

            Log::info('Wallet debited', [
                'user_id' => $user->id,
                'amount_kobo' => $amountKobo,
                'balance_after' => $wallet->balance_kobo,
                'transaction_id' => $transaction->id,
            ]);

            return $transaction;
        });
    }

    public function fundWallet(User $user, float $amount): Transaction
    {
        $amountKobo = (int) round($amount * 100);

        return $this->credit(
            user: $user,
            amountKobo: $amountKobo,
            notes: 'Wallet funding (simulated deposit)'
        );
    }

    public function hasSufficientBalance(User $user, int $amountKobo): bool
    {
        return $user->wallet->hasSufficientBalance($amountKobo);
    }

    protected function createTransaction(
        User $user,
        Wallet $wallet,
        string $type,
        int $amountKobo,
        int $balanceAfter,
        ?string $notes = null,
        array $metadata = []
    ): Transaction {
        return Transaction::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'type' => $type,
            'amount_kobo' => $amountKobo,
            'status' => Transaction::STATUS_COMPLETED,
            'balance_after' => $balanceAfter,
            'notes' => $notes,
            'metadata' => $metadata,
        ]);
    }

    public function getTransactionHistory(User $user, int $perPage = 20)
    {
        return $user->wallet->transactions()
            ->latest()
            ->paginate($perPage);
    }
}
