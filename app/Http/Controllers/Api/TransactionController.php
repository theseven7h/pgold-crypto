<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->transactions()->with('cryptoTrade');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->from);
        }

        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->to);
        }

        if ($request->has('reference')) {
            $query->where('reference', 'like', '%' . $request->reference . '%');
        }

        $perPage = $request->input('per_page', 20);
        $transactions = $query->latest()->paginate($perPage);

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


    public function show(Request $request, string $reference): JsonResponse
    {
        $transaction = $request->user()->transactions()
            ->with('cryptoTrade')
            ->where('reference', $reference)
            ->firstOrFail();

        return response()->json([
            'data' => new TransactionResource($transaction),
        ]);
    }
}
