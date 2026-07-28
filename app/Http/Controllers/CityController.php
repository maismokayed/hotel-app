<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Http\Resources\CityResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CityController extends Controller
{
    public function index()
    {
        return CityResource::collection(City::with('media')->get());
    }

    public function uploadImage(Request $request, City $city)
    {
        $request->validate([
            'image' => 'required|image|max:2048',
        ]);

        $city->clearMediaCollection('images');

        $city->addMedia($request->file('image'))
            ->toMediaCollection('images');

        return response()->json([
            'success' => true,
            'message' => [
                'ar' => 'تم رفع الصورة بنجاح.',
                'en' => 'Image uploaded successfully.',
            ],
            'data' => [
                'image_url' => $city->getFirstMediaUrl('images'),
            ],
        ], 200);
    }

    public function deleteImage(City $city): JsonResponse
    {
        $city->clearMediaCollection('images');

        return response()->json([
            'success' => true,
            'message' => [
                'ar' => 'تم حذف الصورة بنجاح.',
                'en' => 'Image deleted successfully.',
            ],
        ], 200);
    }
}
