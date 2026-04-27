<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hotel;
use App\Models\User;
use App\Http\Requests\StoreHotelRequest;
use App\Http\Requests\UpdateHotelRequest;

class HotelController extends Controller
{
    public function store(StoreHotelRequest $request)
{
  $data = $request->validated();

    $similarHotel = Hotel::where('name', $data['name'])
        ->where('city', $data['city'])
        ->first();

    if ($similarHotel) {
        return response()->json([
            'message' => '⚠️ Warning: Similar hotel exists',
            'warning' => true,
            'existing_hotel' => $similarHotel
        ], 200);
    }

    $hotel = Hotel::create([
        'name' => $data['name'],
        'description' => $data['description'] ?? null,
        'city' => $data['city'],
        'address' => $data['address'],
        'phone' => $data['phone'] ?? null,
        'email' => $data['email'] ?? null,
        'star_rating' => $data['star_rating'] ?? null,
        'is_active' => true,
        'user_id' => auth()->id(),
    ]);

    return response()->json([
        'message' => 'Hotel created successfully',
        'hotel' => $hotel
    ]);
}

    public function index()
{
    $hotels = Hotel::where('is_active', true)->latest()->get();

    return response()->json($hotels);
}

public function show(Hotel $hotel)
{
    return response()->json($hotel);
}

public function update(UpdateHotelRequest $request, Hotel $hotel)
{
    
    if (
        $hotel->user_id !== auth()->id() &&
        !auth()->user()->hasRole('admin')
    ) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $data = $request->validated();
    $hotel->update($data);

    return response()->json([
        'message' => 'Hotel updated successfully',
            'hotel' => $hotel->fresh()
    ]);
}

public function destroy(Hotel $hotel)
{
    if (
        $hotel->user_id !== auth()->id() &&
        !auth()->user()->hasRole('admin')
    ) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $hotel->delete();

    return response()->json([
        'message' => 'Hotel deleted successfully'
    ]);
}
}
