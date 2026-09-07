<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class WalletDepositRequest extends FormRequest
{
    public const MIN_AMOUNT = 5;
    public const MAX_AMOUNT = 3000;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:' . self::MIN_AMOUNT . '|max:' . self::MAX_AMOUNT,
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $failed = $validator->failed();

        $message = ['ar' => 'البيانات المدخلة غير صحيحة', 'en' => 'The given data was invalid'];

        if (isset($failed['amount'])) {
            $rule = array_key_first($failed['amount']);

            $message = match ($rule) {
                'Max' => [
                    'ar' => 'المبلغ المدخل أكبر من الحد المسموح ($' . self::MAX_AMOUNT . ')',
                    'en' => 'The entered amount exceeds the maximum allowed ($' . self::MAX_AMOUNT . ')',
                ],
                'Min' => [
                    'ar' => 'المبلغ المدخل أقل من الحد الأدنى المسموح ($' . self::MIN_AMOUNT . ')',
                    'en' => 'The entered amount is below the minimum allowed ($' . self::MIN_AMOUNT . ')',
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
