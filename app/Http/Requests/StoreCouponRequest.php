<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'           => 'required|string|unique:coupons,code',
            'discount_type'  => 'required|in:percentage,fixed',
            'discount_value' => [
                'required',
                'numeric',
                'min:1',
                Rule::when(
                    $this->input('discount_type') === 'percentage',
                    ['max:100']
                ),
            ],
            'max_uses'       => 'nullable|integer|min:1',
            'expires_at'     => 'nullable|date|after:today',
            'is_active'      => 'boolean',
        ];
    }
}
