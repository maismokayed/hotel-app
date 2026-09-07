<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class WalletDepositRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:10|max:500000'
        ];
    }
    protected function failedValidation(Validator $validator)
    {
        $failed = $validator->failed(); // بيرجع شو القاعدة يلي فشلت لكل حقل

        $message = ['ar' => 'البيانات المدخلة غير صحيحة', 'en' => 'The given data was invalid'];

        if (isset($failed['amount'])) {
            $rule = array_key_first($failed['amount']); // Required / Numeric / Min / Max

            $message = match ($rule) {
                'Max' => [
                    'ar' => 'المبلغ المدخل أكبر من الحد المسموح (500,000)',
                    'en' => 'The entered amount exceeds the maximum allowed (500,000)',
                ],
                'Min' => [
                    'ar' => 'المبلغ المدخل أقل من الحد الأدنى المسموح (10)',
                    'en' => 'The entered amount is below the minimum allowed (10)',
                ],
                'Numeric' => [
                    'ar' => 'المبلغ يجب أن يكون رقمًا',
                    'en' => 'The amount must be a number',
                ],
                'Required' => [
                    'ar' => 'الرجاء إدخال المبلغ',
                    'en' => 'Please enter an amount',
                ],
                default => $message,
            };
        }

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $validator->errors(),
        ], 422));
    }
}
