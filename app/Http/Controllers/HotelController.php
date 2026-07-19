<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Http\Requests\StoreHotelRequest;
use App\Http\Requests\UpdateHotelRequest;
use App\Http\Resources\HotelResource;
use App\Http\Requests\TransferHotelRequest;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateHotelStatusRequest;

class HotelController extends Controller
{
    public function index(Request $request)
    {
        $hotels = Hotel::query()
            ->when($request->filled('name'), function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('name_ar', 'like', "%{$request->name}%")
                        ->orWhere('name_en', 'like', "%{$request->name}%")
                        ->orWhereHas('city', function ($cityQuery) use ($request) {
                            $cityQuery->where('name_ar', 'like', "%{$request->name}%")
                                ->orWhere('name_en', 'like', "%{$request->name}%");
                        });
                });
            })
            ->when($request->filled('city_id'), fn($q) =>
            $q->where('city_id', $request->city_id))
            ->when($request->filled('star_rating'), fn($q) =>
            $q->where('star_rating', $request->star_rating))
            ->withCount('bookings')
            ->when($request->sort === 'popular', function ($q) {
                $q->orderByDesc('bookings_count');
            }, function ($q) {
                $q->latest();
            })
            ->paginate(10);

        return HotelResource::collection($hotels->load('user', 'city', 'services'));
    }
    public function show(Hotel $hotel)
    {

        return new HotelResource($hotel->load('user', 'city', 'services'));
    }

    public function store(StoreHotelRequest $request)
    {
        $data = $request->validated();

        $similarHotel = Hotel::where('name_en', trim($data['name_en']))
            ->where('city_id', $data['city_id'])
            ->first();

        if ($similarHotel) {
            return response()->json([
                'message' => 'Hotel already exists in this city',
                'existing_hotel' => new HotelResource($similarHotel->load('user', 'city'))
            ], 409);
        }

        $hotel = new Hotel();
        $hotel->name_ar = trim($data['name_ar']);
        $hotel->name_en = trim($data['name_en']);
        $hotel->description_ar = $data['description_ar'] ?? null;
        $hotel->description_en = $data['description_en'] ?? null;
        $hotel->address_ar = trim($data['address_ar']);
        $hotel->address_en = trim($data['address_en']);
        $hotel->city_id = $data['city_id'];
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

        $similarHotel = Hotel::where(
            'name_en',
            trim($data['name_en'] ?? $hotel->name_en)
        )
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
            'name_ar' => trim($data['name_ar'] ?? $hotel->name_ar),
            'name_en' => trim($data['name_en'] ?? $hotel->name_en),

            'description_ar' => $data['description_ar'] ?? $hotel->description_ar,
            'description_en' => $data['description_en'] ?? $hotel->description_en,

            'city_id' => $data['city_id'] ?? $hotel->city_id,

            'address_ar' => trim($data['address_ar'] ?? $hotel->address_ar),
            'address_en' => trim($data['address_en'] ?? $hotel->address_en),

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
    public function syncServices(Request $request, Hotel $hotel)
    {
        $this->authorizeHotelAccess($hotel);

        $validated = $request->validate([
            'service_ids'   => 'required|array',
            'service_ids.*' => 'exists:services,id',
        ]);

        $hotel->services()->sync($validated['service_ids']);

        return new HotelResource($hotel->load('user', 'city', 'services'));
    }
    public function updateStatus(UpdateHotelStatusRequest $request, Hotel $hotel)
    {
        $this->authorizeHotelAccess($hotel);

        $hotel->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        return response()->json([
            'message' => 'Hotel status updated successfully',
            'hotel' => new HotelResource(
                $hotel->fresh()->load('user', 'city', 'services')
            ),
        ]);
    }
}
