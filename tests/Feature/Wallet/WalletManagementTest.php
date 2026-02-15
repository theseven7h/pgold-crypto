<?php

namespace Tests\Feature\Wallet;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_user_can_view_their_wallet()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/wallet');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'balance',
                    'balance_kobo',
                    'formatted_balance',
                ],
            ]);

        $this->assertEquals('0.00', $response->json('data.balance'));
    }

    public function test_user_can_fund_wallet()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/wallet/fund', [
            'amount' => 50000.00,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'balance',
                    'formatted_balance',
                    'transaction',
                ],
            ]);

        $this->assertEquals('50000.00', $response->json('data.balance'));

        $this->user->wallet->refresh();
        $this->assertEquals(5000000, $this->user->wallet->balance_kobo);
    }

    public function test_funding_wallet_creates_transaction_record()
    {
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/wallet/fund', [
            'amount' => 10000.00,
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'type' => 'wallet_deposit',
            'amount_kobo' => 1000000,
            'status' => 'completed',
        ]);
    }

    public function test_wallet_funding_requires_minimum_amount()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/wallet/fund', [
            'amount' => 500.00,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_user_can_view_wallet_transactions()
    {
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/wallet/fund', ['amount' => 10000.00]);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/wallet/fund', ['amount' => 5000.00]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/wallet/transactions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'reference',
                        'type',
                        'amount',
                        'status',
                        'created_at',
                    ],
                ],
                'meta',
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_unauthenticated_user_cannot_access_wallet()
    {
        $response = $this->getJson('/api/wallet');
        $response->assertStatus(401);
    }
}
