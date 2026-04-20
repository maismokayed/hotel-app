<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hotel;
use App\Models\User;



class HotelController extends Controller
{
    public function store(Request $request)
{
    $data = $request->validate([
        'name' => 'required|string|max:100',
        'description' => 'nullable|string',
        'city' => 'required|string',
        'address' => 'nullable|string',
        'phone' => 'nullable|string|max:20',
        'email' => 'nullable|email',
        'star_rating' => 'nullable|integer|min:1|max:5',
    ]);

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

public function update(Request $request, Hotel $hotel)
{
    
    if (
        $hotel->user_id !== auth()->id() &&
        !auth()->user()->hasRole('admin')
    ) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $data = $request->validate([
        'name' => 'sometimes|string|max:100',
        'description' => 'nullable|string',
        'city' => 'sometimes|string',
        'address' => 'nullable|string',
        'phone' => 'nullable|string|max:20',
        'email' => 'nullable|email',
        'star_rating' => 'nullable|integer|min:1|max:5',
        'is_active' => 'boolean'
    ]);

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
