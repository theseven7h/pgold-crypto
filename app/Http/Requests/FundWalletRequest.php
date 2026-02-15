<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FundWalletRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        $minimumAmount = config('trading.minimum_transaction_amount', 100000) / 100;

        return [
            'amount' => [
                'required',
                'numeric',
                'min:' . $minimumAmount,
                'max:10000000',
            ],
        ];
    }


    public function messages(): array
    {
        $minimumAmount = config('trading.minimum_transaction_amount', 100000) / 100;

        return [
            'amount.required' => 'Amount is required',
            'amount.numeric' => 'Amount must be a valid number',
            'amount.min' => 'Minimum funding amount is ₦' . number_format($minimumAmount, 2),
            'amount.max' => 'Maximum funding amount is ₦10,000,000.00',
        ];
    }
}
