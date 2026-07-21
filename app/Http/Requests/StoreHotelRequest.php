<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHotelRequest extends FormRequest
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
            'name_ar' => 'required|string|max:100',
            'name_en' => 'required|string|max:100',

            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',

            'city_id' => 'required|exists:cities,id',

            'address_ar' => 'required|string',
            'address_en' => 'required|string',

            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'star_rating' => 'nullable|integer|min:1|max:5',

            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpg,jpeg,png,jfif|max:10240',
        ];
    }
}
