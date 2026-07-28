<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingRequest extends FormRequest
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
            // 'cancelled' is intentionally excluded here — cancelling a
            // booking must always go through BookingController::cancel(),
            // which handles the refund/fee logic. Allowing it here would
            // let a booking be cancelled with no money ever moving.
            'status' => 'required|in:pending,confirmed,completed',
        ];
    }
}
