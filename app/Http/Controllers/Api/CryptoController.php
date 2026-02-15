<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\CryptoServiceException;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InvalidTradeException;
use App\Http\Controllers\Controller;
use App\Http\Requests\BuyCryptoRequest;
use App\Http\Requests\SellCryptoRequest;
use App\Http\Resources\CryptoTradeResource;
use App\Services\CoinGeckoService;
use App\Services\CryptoTradeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CryptoController extends Controller
{
    public function __construct(
        protected CryptoTradeService $cryptoTradeService,
        protected CoinGeckoService $coinGeckoService
    ) {
    }


    public function rates(): JsonResponse
    {
        try {
            $rates = $this->coinGeckoService->getRates();

            return response()->json([
                'data' => $rates,
                'cached' => $this->coinGeckoService->hasCachedRates(),
                'cache_ttl' => $this->coinGeckoService->getCacheTtl(),
            ]);
        } catch (\Exception $e) {
            throw new CryptoServiceException($e->getMessage());
        }
    }


    public function buy(BuyCryptoRequest $request): JsonResponse
    {
        try {
            $trade = $this->cryptoTradeService->buy(
                user: $request->user(),
                cryptoType: $request->crypto_type,
                nairaAmount: $request->amount_naira
            );

            return response()->json([
                'message' => 'Cryptocurrency purchased successfully',
                'data' => new CryptoTradeResource($trade),
            ], 200);

        } catch (InsufficientFundsException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);

        } catch (InvalidTradeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Trade execution failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }


    public function sell(SellCryptoRequest $request): JsonResponse
    {
        try {
            $trade = $this->cryptoTradeService->sell(
                user: $request->user(),
                cryptoType: $request->crypto_type,
                cryptoAmount: $request->amount_crypto
            );

            return response()->json([
                'message' => 'Cryptocurrency sold successfully',
                'data' => new CryptoTradeResource($trade),
            ], 200);

        } catch (InvalidTradeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Trade execution failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }


    public function trades(Request $request): JsonResponse
    {
        $filters = $request->only(['crypto_type', 'trade_type', 'status', 'from', 'to']);
        $perPage = $request->input('per_page', 20);

        $trades = $this->cryptoTradeService->getTradeHistory(
            user: $request->user(),
            filters: $filters,
            perPage: $perPage
        );

        return CryptoTradeResource::collection($trades)
            ->additional([
                'meta' => [
                    'total' => $trades->total(),
                    'current_page' => $trades->currentPage(),
                    'per_page' => $trades->perPage(),
                    'last_page' => $trades->lastPage(),
                ],
            ])
            ->response();
    }


    public function buyPreview(Request $request): JsonResponse
    {
        $request->validate([
            'crypto_type' => 'required|string|in:btc,eth,usdt',
            'amount_naira' => 'required|numeric|min:0',
        ]);

        try {
            $preview = $this->cryptoTradeService->calculateBuyPreview(
                cryptoType: strtolower($request->crypto_type),
                nairaAmount: $request->amount_naira
            );

            return response()->json([
                'data' => $preview,
            ]);
        } catch (\Exception $e) {
            throw new CryptoServiceException('Unable to calculate preview: ' . $e->getMessage());
        }
    }
    public function sellPreview(Request $request): JsonResponse
    {
        $request->validate([
            'crypto_type' => 'required|string|in:btc,eth,usdt',
            'amount_crypto' => 'required|numeric|gt:0',
        ]);

        try {
            $preview = $this->cryptoTradeService->calculateSellPreview(
                cryptoType: strtolower($request->crypto_type),
                cryptoAmount: $request->amount_crypto
            );

            return response()->json([
                'data' => $preview,
            ]);
        } catch (\Exception $e) {
            throw new CryptoServiceException('Unable to calculate preview: ' . $e->getMessage());
        }
    }
}
