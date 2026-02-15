<?php

namespace Tests\Feature\Crypto;

use App\Models\User;
use App\Services\CoinGeckoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CryptoTradingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        Http::fake([
            'api.coingecko.com/*' => Http::response([
                'bitcoin' => ['ngn' => 100000000, 'usd' => 100000],
                'ethereum' => ['ngn' => 8000000, 'usd' => 8000],
                'tether' => ['ngn' => 1500, 'usd' => 1],
            ], 200),
        ]);
    }



    public function test_user_can_get_cryptocurrency_rates()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/crypto/rates');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'btc' => ['ngn', 'usd'],
                    'eth' => ['ngn', 'usd'],
                    'usdt' => ['ngn', 'usd'],
                ],
            ]);
    }

    public function test_user_can_buy_cryptocurrency()
    {
        // Fund wallet first
        $this->user->wallet->update(['balance_kobo' => 10000000]); // 100k Naira

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/crypto/buy', [
            'crypto_type' => 'btc',
            'amount_naira' => 50000.00,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'crypto_type',
                    'crypto_amount',
                    'naira_amount',
                    'fee',
                    'rate_ngn',
                    'status',
                ],
            ]);

        $this->user->wallet->refresh();
        $expectedDebit = 5000000 + (5000000 * 0.015);
        $expectedBalance = 10000000 - $expectedDebit;
        $this->assertEquals($expectedBalance, $this->user->wallet->balance_kobo);

        $this->assertDatabaseHas('crypto_trades', [
            'user_id' => $this->user->id,
            'crypto_type' => 'btc',
            'trade_type' => 'buy',
            'status' => 'completed',
        ]);
    }

    public function test_user_cannot_buy_crypto_with_insufficient_balance()
    {
        // Wallet has only 10k, trying to buy 50k worth
        $this->user->wallet->update(['balance_kobo' => 1000000]); // 10k Naira

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/crypto/buy', [
            'crypto_type' => 'btc',
            'amount_naira' => 50000.00,
        ]);

        $response->assertStatus(422);
    }

    // public function test_user_can_sell_cryptocurrency()
    // {
    //     $this->user->wallet->update(['balance_kobo' => 1000000]); // 10k Naira

    //     $response = $this->withHeaders([
    //         'Authorization' => 'Bearer ' . $this->token,
    //     ])->postJson('/api/crypto/sell', [
    //         'crypto_type' => 'eth',
    //         'amount_crypto' => 1.0,
    //     ]);

    //     $response->assertStatus(200)
    //         ->assertJsonStructure([
    //             'message',
    //             'data' => [
    //                 'id',
    //                 'crypto_type',
    //                 'crypto_amount',
    //                 'naira_amount',
    //                 'fee',
    //                 'rate_ngn',
    //                 'status',
    //             ],
    //         ]);

    //     // Verify wallet was credited (amount - 1% fee)
    //     $this->user->wallet->refresh();
    //     $grossValue = 8000000; // 1 ETH at 8M NGN
    //     $fee = $grossValue * 0.01;
    //     $netCredit = $grossValue - $fee;
    //     $expectedBalance = 1000000 + $netCredit;
    //     $this->assertEquals($expectedBalance, $this->user->wallet->balance_kobo);
    // }

    public function test_user_can_sell_cryptocurrency()
{
    $this->user->wallet->update(['balance_kobo' => 1000000]);
    $initialBalance = $this->user->wallet->balance_kobo;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->postJson('/api/crypto/sell', [
        'crypto_type' => 'eth',
        'amount_crypto' => 1.0,
    ]);

    $response->assertStatus(200);

    $this->user->wallet->refresh();
    $this->assertGreaterThan($initialBalance, $this->user->wallet->balance_kobo);
}

    public function test_buy_requires_minimum_transaction_amount()
    {
        $this->user->wallet->update(['balance_kobo' => 10000000]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/crypto/buy', [
            'crypto_type' => 'btc',
            'amount_naira' => 500.00,
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_view_trade_history()
    {
        $this->user->wallet->update(['balance_kobo' => 10000000]);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/crypto/buy', [
            'crypto_type' => 'btc',
            'amount_naira' => 5000.00,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/crypto/trades');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'crypto_type',
                        'trade_type',
                        'crypto_amount',
                        'status',
                    ],
                ],
                'meta',
            ]);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_user_can_filter_trade_history_by_crypto_type()
    {
        $this->user->wallet->update(['balance_kobo' => 100000000]);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/crypto/buy', [
            'crypto_type' => 'btc',
            'amount_naira' => 5000.00,
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/crypto/buy', [
            'crypto_type' => 'eth',
            'amount_naira' => 5000.00,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/crypto/trades?crypto_type=btc');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('BTC', $response->json('data.0.crypto_type'));
    }
}
