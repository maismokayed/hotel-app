<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\RoomType;
use App\Enums\RoomStatus;

class StoreRoomRequest extends FormRequest
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
            'hotel_id' => 'required|exists:hotels,id',
            'room_number' => 'required|string|max:50',
            'type' => 'required|in:' . implode(',', array_column(RoomType::cases(), 'value')),
            'capacity' => 'required|integer|min:1|max:12',
            'price_per_night' => 'required|numeric|min:0',
            'status' => 'nullable|in:' . implode(',', array_column(RoomStatus::cases(), 'value')),
        ];
    }
}
