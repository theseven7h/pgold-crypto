<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CryptoController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::prefix('wallet')->group(function () {
        Route::get('/', [WalletController::class, 'show']);
        Route::post('/fund', [WalletController::class, 'fund']);
        Route::get('/transactions', [WalletController::class, 'transactions']);
    });

    Route::prefix('crypto')->group(function () {
        Route::get('/rates', [CryptoController::class, 'rates']);
        Route::post('/buy', [CryptoController::class, 'buy'])->middleware('throttle:10,1'); // 10 trades per minute
        Route::post('/sell', [CryptoController::class, 'sell'])->middleware('throttle:10,1');
        Route::get('/trades', [CryptoController::class, 'trades']);
        Route::post('/buy/preview', [CryptoController::class, 'buyPreview']);
        Route::post('/sell/preview', [CryptoController::class, 'sellPreview']);
    });

    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'index']);
        Route::get('/{reference}', [TransactionController::class, 'show']);
    });
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
    ]);
});
