<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\CryptoTradeService;
use App\Services\WalletService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{

    public function run(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $walletService = app(WalletService::class);
        $walletService->fundWallet($user, 100000.00);

        $this->command->info('Test user created: test@example.com / password');
        $this->command->info('Wallet funded with ₦100,000.00');

        try {
            $cryptoTradeService = app(CryptoTradeService::class);

            $cryptoTradeService->buy($user, 'btc', 10000.00);
            $this->command->info('Sample BTC buy trade created');

            $cryptoTradeService->sell($user, 'eth', 0.5);
            $this->command->info('Sample ETH sell trade created');

        } catch (\Exception $e) {
            $this->command->warn('Could not create sample trades (CoinGecko API may be unavailable)');
            $this->command->warn('Error: ' . $e->getMessage());
        }

        $this->command->info('');
        $this->command->info('Database seeding completed!');
        $this->command->info('');
        $this->command->info('You can now login with:');
        $this->command->info('Email: test@example.com');
        $this->command->info('Password: password');
    }
}
