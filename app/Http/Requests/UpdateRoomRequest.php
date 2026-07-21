<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\RoomType;
use App\Enums\RoomStatus;


class UpdateRoomRequest extends FormRequest
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
            'room_number' => 'sometimes|string|max:50',
            'type' => 'sometimes|in:' . implode(',', array_column(RoomType::cases(), 'value')),
            'capacity' => 'sometimes|integer|min:1|max:10',
            'price_per_night' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:' . implode(',', array_column(RoomStatus::cases(), 'value')),
            'image' => 'nullable|image|mimes:jpg,jpeg,png,jfif|max:10240',

        ];
    }
}
