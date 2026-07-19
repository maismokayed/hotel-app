<?php

namespace App\Http\Requests;

use App\Enums\RoomType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hotel_id'          => 'required|exists:hotels,id',

            'rooms'             => 'required|array|min:1',
            'rooms.*.type'      => ['required', 'string', Rule::in(array_column(RoomType::cases(), 'value'))],
            'rooms.*.quantity'  => 'required|integer|min:1|max:20',

            'check_in_date'     => 'required|date|after:today',
            'check_out_date'    => 'required|date|after:check_in_date',
            'number_of_guests'  => 'required|integer|min:1',
            'coupon_code'       => 'nullable|string|exists:coupons,code',
            'payment_method'    => 'required|in:wallet,cash',
        ];
    }
}
