<?php

namespace App\Http\Controllers;


use App\Models\Hotel;
use App\Http\Requests\StoreHotelRequest;
use App\Http\Requests\UpdateHotelRequest;
use App\Http\Resources\HotelResource;
use App\Http\Requests\TransferHotelRequest;

class HotelController extends Controller
{

    public function index()
{
  $hotels = Hotel::where('is_active', true)
    ->latest()
    ->paginate(10);

   return HotelResource::collection($hotels->load('user'));
}

public function show(Hotel $hotel)
    {
        if (!$hotel->is_active) {
            return response()->json([
                'message' => 'Hotel not found'
            ], 404);
        }
   return new HotelResource($hotel->load('user'));
}
public function store(StoreHotelRequest $request)
    {
        $data = $request->validated();

        $similarHotel = Hotel::whereRaw(
                'LOWER(name) = ?',
                [strtolower(trim($data['name']))]
            )
            ->whereRaw(
                'LOWER(city) = ?',
                [strtolower(trim($data['city']))]
            )
            ->first();

        if ($similarHotel) {
            return response()->json([
                'message' => 'Hotel already exists in this city',
                'existing_hotel' => $similarHotel
            ], 409);
        }

       $hotel = new Hotel();

$hotel->name = trim($data['name']);
$hotel->description = $data['description'] ?? null;
$hotel->city = trim($data['city']);
$hotel->address = trim($data['address']);
$hotel->phone = $data['phone'] ?? null;
$hotel->email = $data['email'] ?? null;
$hotel->star_rating = $data['star_rating'] ?? null;

$hotel->is_active = true;
$hotel->user_id = auth()->id();

$hotel->save();

      return response()->json([
    'message' => 'Hotel created successfully',
    'hotel' => new HotelResource($hotel->load('user'))
], 201);
    }
    /**
     * Update hotel
     */
    public function update(UpdateHotelRequest $request, Hotel $hotel)
{
    $this->authorizeHotelAccess($hotel);

    $data = $request->validated();

    // Prevent duplicate hotel after update
    $similarHotel = Hotel::whereRaw(
            'LOWER(name) = ?',
            [strtolower(trim($data['name'] ?? $hotel->name))]
        )
        ->whereRaw(
            'LOWER(city) = ?',
            [strtolower(trim($data['city'] ?? $hotel->city))]
        )
        ->where('id', '!=', $hotel->id)
        ->first();

    if ($similarHotel) {
        return response()->json([
            'message' => 'Another hotel with same name already exists in this city',
            'existing_hotel' => $similarHotel
        ], 409);
    }

    $hotel->update([
        'name' => trim($data['name'] ?? $hotel->name),
        'description' => $data['description'] ?? $hotel->description,
        'city' => trim($data['city'] ?? $hotel->city),
        'address' => trim($data['address'] ?? $hotel->address),
        'phone' => $data['phone'] ?? $hotel->phone,
        'email' => $data['email'] ?? $hotel->email,
        'star_rating' => $data['star_rating'] ?? $hotel->star_rating,
    ]);

    return response()->json([
        'message' => 'Hotel updated successfully',
        'hotel' => new HotelResource($hotel->fresh()->load('user'))
    ]);
}
    /**
     * Delete hotel
     */
    public function destroy(Hotel $hotel)
    {
        $this->authorizeHotelAccess($hotel);

        $hotel->delete();

        return response()->json([
            'message' => 'Hotel deleted successfully'
        ]);
    }

    /**
     * Authorization helper
     */
    private function authorizeHotelAccess(Hotel $hotel)
    {
        $user = auth()->user();

        if (
            !$user ||
            (
                $hotel->user_id !== $user->id &&
                !$user->hasRole('admin')
            )
        ) {
            abort(403, 'Unauthorized');
        }
    }
    public function transfer(TransferHotelRequest $request, Hotel $hotel)
{
    $newOwner = \App\Models\User::find($request->user_id);

    // تأكد إن الشخص الجديد عنده role manager أو admin
    if (!$newOwner->hasRole('manager') && !$newOwner->hasRole('admin')) {
        return response()->json([
            'message' => 'User must be a manager or admin'
        ], 422);
    }

    $hotel->update(['user_id' => $request->user_id]);

    return response()->json([
        'message' => 'Hotel transferred successfully',
        'hotel' => new HotelResource($hotel->fresh()->load('user'))
    ]);
}
}