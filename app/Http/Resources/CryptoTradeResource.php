<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CryptoTradeResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'crypto_type' => strtoupper($this->crypto_type),
            'crypto_name' => $this->crypto_name,
            'trade_type' => $this->trade_type,
            'crypto_amount' => (string) $this->crypto_amount,
            'naira_amount' => number_format($this->naira_amount, 2, '.', ''),
            'naira_amount_kobo' => $this->naira_amount_kobo,
            'formatted_naira_amount' => $this->formatted_naira_amount,
            'rate_ngn' => number_format($this->rate_ngn, 2, '.', ''),
            'fee' => number_format($this->fee, 2, '.', ''),
            'fee_kobo' => $this->fee_kobo,
            'formatted_fee' => $this->formatted_fee,
            'fee_percentage' => number_format($this->fee_percentage, 2, '.', ''),
            'status' => $this->status,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->toISOString(),

            'transaction_reference' => $this->when(
                $this->relationLoaded('transaction'),
                $this->transaction?->reference
            ),
        ];
    }
}
