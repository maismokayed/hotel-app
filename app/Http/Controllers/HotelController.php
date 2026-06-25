<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Http\Requests\StoreHotelRequest;
use App\Http\Requests\UpdateHotelRequest;
use App\Http\Resources\HotelResource;
use App\Http\Requests\TransferHotelRequest;
use Illuminate\Http\Request;

class HotelController extends Controller
{
    public function index(Request $request)
    {
        $hotels = Hotel::where('is_active', true)
            ->when($request->name, fn($q) =>
            $q->where('name', 'like', "%{$request->name}%"))
            ->when($request->city_id, fn($q) =>
            $q->where('city_id', $request->city_id))
            ->when($request->star_rating, fn($q) =>
            $q->where('star_rating', $request->star_rating))
            ->latest()
            ->paginate(10);

        return HotelResource::collection($hotels->load('user', 'city'));
    }

    public function show(Hotel $hotel)
    {
        if (!$hotel->is_active) {
            return response()->json(['message' => 'Hotel not found'], 404);
        }
        return new HotelResource($hotel->load('user', 'city'));
    }

    public function store(StoreHotelRequest $request)
    {
        $data = $request->validated();

        $similarHotel = Hotel::where('name', trim($data['name']))
            ->where('city_id', $data['city_id'])
            ->first();

        if ($similarHotel) {
            return response()->json([
                'message' => 'Hotel already exists in this city',
                'existing_hotel' => new HotelResource($similarHotel->load('user', 'city'))
            ], 409);
        }

        $hotel = new Hotel();
        $hotel->name = trim($data['name']);
        $hotel->description = $data['description'] ?? null;
        $hotel->city_id = $data['city_id'];
        $hotel->address = trim($data['address']);
        $hotel->phone = $data['phone'] ?? null;
        $hotel->email = $data['email'] ?? null;
        $hotel->star_rating = $data['star_rating'] ?? null;
        $hotel->is_active = true;
        $hotel->user_id = auth()->id();
        $hotel->save();

        return response()->json([
            'message' => 'Hotel created successfully',
            'hotel' => new HotelResource($hotel->load('user', 'city'))
        ], 201);
    }

    public function update(UpdateHotelRequest $request, Hotel $hotel)
    {
        $this->authorizeHotelAccess($hotel);

        $data = $request->validated();

        $similarHotel = Hotel::where('name', trim($data['name'] ?? $hotel->name))
            ->where('city_id', $data['city_id'] ?? $hotel->city_id)
            ->where('id', '!=', $hotel->id)
            ->first();

        if ($similarHotel) {
            return response()->json([
                'message' => 'Another hotel with same name already exists in this city',
                'existing_hotel' => new HotelResource($similarHotel->load('user', 'city'))
            ], 409);
        }

        $hotel->update([
            'name' => trim($data['name'] ?? $hotel->name),
            'description' => $data['description'] ?? $hotel->description,
            'city_id' => $data['city_id'] ?? $hotel->city_id,
            'address' => trim($data['address'] ?? $hotel->address),
            'phone' => $data['phone'] ?? $hotel->phone,
            'email' => $data['email'] ?? $hotel->email,
            'star_rating' => $data['star_rating'] ?? $hotel->star_rating,
        ]);

        return response()->json([
            'message' => 'Hotel updated successfully',
            'hotel' => new HotelResource($hotel->fresh()->load('user', 'city'))
        ]);
    }

    public function destroy(Hotel $hotel)
    {
        $this->authorizeHotelAccess($hotel);
        $hotel->delete();

        return response()->json([
            'message' => 'Hotel deleted successfully'
        ]);
    }

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

        if (!$newOwner) {
            return response()->json(['message' => 'المستخدم غير موجود.'], 404);
        }

        if (!$newOwner->hasRole('manager') && !$newOwner->hasRole('admin')) {
            return response()->json([
                'message' => 'User must be a manager or admin'
            ], 422);
        }

        $hotel->update(['user_id' => $request->user_id]);

        return response()->json([
            'message' => 'Hotel transferred successfully',
            'hotel' => new HotelResource($hotel->fresh()->load('user', 'city'))
        ]);
    }

    public function uploadImages(Request $request, Hotel $hotel)
    {
        $this->authorizeHotelAccess($hotel);

        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,jfif|max:10240',
        ]);

        $media = $hotel->addMedia($request->file('image'))
            ->toMediaCollection('images');

        return response()->json([
            'id'  => $media->id,
            'url' => $media->getUrl(),
        ]);
    }

    public function getImages(Hotel $hotel)
    {
        $images = $hotel->getMedia('images')->map(fn($media) => [
            'id'         => $media->id,
            'url'        => $media->getUrl(),
            'name'       => $media->file_name,
            'mime_type'  => $media->mime_type,
            'created_at' => $media->created_at,
        ]);

        return response()->json([
            'images' => $images,
        ]);
    }

    public function deleteImage(Hotel $hotel, $mediaId)
    {
        $this->authorizeHotelAccess($hotel);

        $media = $hotel->getMedia('images')->find($mediaId);

        if (!$media) {
            return response()->json([
                'message' => 'Image not found',
            ], 404);
        }

        $media->delete();

        return response()->json([
            'message' => 'Image deleted successfully',
        ]);
    }
}
