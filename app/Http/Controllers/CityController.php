<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Http\Resources\CityResource;
use Illuminate\Http\Request;

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
            'message' => 'Image uploaded successfully',
            'image_url' => $city->getFirstMediaUrl('images')
        ]);
    }
}
