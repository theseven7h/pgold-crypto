<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FundWalletRequest;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\WalletResource;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(protected WalletService $walletService)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $wallet = $this->walletService->getWallet($request->user());

        return response()->json([
            'data' => new WalletResource($wallet),
        ]);
    }


    public function fund(FundWalletRequest $request): JsonResponse
    {
        $transaction = $this->walletService->fundWallet(
            user: $request->user(),
            amount: $request->amount
        );

        $wallet = $request->user()->wallet->fresh();

        return response()->json([
            'message' => 'Wallet funded successfully',
            'data' => [
                'balance' => number_format($wallet->balance, 2, '.', ''),
                'formatted_balance' => $wallet->formatted_balance,
                'transaction' => new TransactionResource($transaction),
            ],
        ]);
    }

    
    public function transactions(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 20);
        $transactions = $this->walletService->getTransactionHistory($request->user(), $perPage);

        return TransactionResource::collection($transactions)
            ->additional([
                'meta' => [
                    'total' => $transactions->total(),
                    'current_page' => $transactions->currentPage(),
                    'per_page' => $transactions->perPage(),
                    'last_page' => $transactions->lastPage(),
                ],
            ])
            ->response();
    }
}
