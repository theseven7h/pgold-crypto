<?php

namespace App\Http\Requests;

use App\Models\CryptoTrade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BuyCryptoRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        $minimumAmount = config('trading.minimum_transaction_amount', 100000) / 100;

        return [
            'crypto_type' => [
                'required',
                'string',
                Rule::in(CryptoTrade::SUPPORTED_CRYPTOS),
            ],
            'amount_naira' => [
                'required',
                'numeric',
                'min:' . $minimumAmount,
                'max:100000000',
            ],
        ];
    }

    public function messages(): array
    {
        $minimumAmount = config('trading.minimum_transaction_amount', 100000) / 100;
        $supportedCryptos = implode(', ', array_map('strtoupper', CryptoTrade::SUPPORTED_CRYPTOS));

        return [
            'crypto_type.required' => 'Cryptocurrency type is required',
            'crypto_type.in' => "Unsupported cryptocurrency. Supported: {$supportedCryptos}",
            'amount_naira.required' => 'Amount in Naira is required',
            'amount_naira.numeric' => 'Amount must be a valid number',
            'amount_naira.min' => 'Minimum transaction amount is ₦' . number_format($minimumAmount, 2),
            'amount_naira.max' => 'Maximum transaction amount is ₦100,000,000.00',
        ];
    }


    protected function prepareForValidation(): void
    {
        if ($this->has('crypto_type')) {
            $this->merge([
                'crypto_type' => strtolower($this->crypto_type),
            ]);
        }
    }
}
