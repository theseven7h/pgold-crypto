<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'type' => $this->type,
            'amount' => number_format($this->amount, 2, '.', ''),
            'amount_kobo' => $this->amount_kobo,
            'formatted_amount' => $this->formatted_amount,
            'status' => $this->status,
            'balance_after' => $this->balance_after ? number_format($this->balance_after / 100, 2, '.', '') : null,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->toISOString(),

            'crypto_trade' => $this->when(
                $this->relationLoaded('cryptoTrade') && $this->cryptoTrade,
                new CryptoTradeResource($this->cryptoTrade)
            ),
        ];
    }
}
