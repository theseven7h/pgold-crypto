<?php

namespace App\Http\Requests;

use App\Models\CryptoTrade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SellCryptoRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'crypto_type' => [
                'required',
                'string',
                Rule::in(CryptoTrade::SUPPORTED_CRYPTOS),
            ],
            'amount_crypto' => [
                'required',
                'numeric',
                'gt:0',
                'max:1000000',
            ],
        ];
    }


    public function messages(): array
    {
        $supportedCryptos = implode(', ', array_map('strtoupper', CryptoTrade::SUPPORTED_CRYPTOS));

        return [
            'crypto_type.required' => 'Cryptocurrency type is required',
            'crypto_type.in' => "Unsupported cryptocurrency. Supported: {$supportedCryptos}",
            'amount_crypto.required' => 'Cryptocurrency amount is required',
            'amount_crypto.numeric' => 'Amount must be a valid number',
            'amount_crypto.gt' => 'Amount must be greater than zero',
            'amount_crypto.max' => 'Maximum amount exceeded',
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
