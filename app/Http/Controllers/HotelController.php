<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Http\Requests\StoreHotelRequest;
use App\Http\Requests\UpdateHotelRequest;
use App\Http\Resources\HotelResource;
use App\Http\Requests\TransferHotelRequest;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateHotelStatusRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\ApiResponse;

class HotelController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'nullable|string|max:100',
            'city_id'     => 'nullable|integer|exists:cities,id',
            'star_rating' => 'nullable|integer|min:1|max:5',
            'sort'        => 'nullable|string|in:popular,latest',
        ]);

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
            ->when(
                $request->filled('city_id'),
                fn($q) => $q->where('city_id', $request->city_id)
            )
            ->when(
                $request->filled('star_rating'),
                fn($q) => $q->where('star_rating', $request->star_rating)
            )
            ->withCount('bookings')
            ->when($request->sort === 'popular', function ($q) {
                $q->orderByDesc('bookings_count');
            }, function ($q) {
                $q->latest();
            })
            ->get();

        // Eager-load media collections too, to avoid N+1 queries when
        // HotelResource calls getFirstMediaUrl()/getMedia() per hotel.
        $hotels->load(
            'user.roles',
            'city.media',
            'services',
            'media'
        );

        return HotelResource::collection($hotels);
    }

    public function show(Hotel $hotel)
    {
        return new HotelResource(
            $hotel->load('user', 'city', 'services', 'media')
        );
    }

    public function store(StoreHotelRequest $request)
    {
        $data = $request->validated();

        $similarHotel = Hotel::where(
            'name_en',
            trim($data['name_en'])
        )
            ->where('city_id', $data['city_id'])
            ->first();

        if ($similarHotel) {
            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'يوجد فندق بنفس الاسم في هذه المدينة.',
                    'en' => 'Hotel already exists in this city.',
                ],
                'data' => [
                    'existing_hotel' => new HotelResource(
                        $similarHotel->load('user', 'city')
                    ),
                ],
            ], 409);
        }

        // Wrapped in a transaction: if any image fails to attach,
        // the hotel row itself is rolled back instead of being left
        // in the database with a partial set of images.
        $hotel = DB::transaction(function () use ($data, $request) {
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

            $hotel->facebook_url = $data['facebook_url'] ?? null;
            $hotel->instagram_url = $data['instagram_url'] ?? null;

            $hotel->star_rating = $data['star_rating'] ?? null;
            $hotel->is_active = true;
            if (!empty($data['user_id']) && auth()->user()->hasRole('admin')) {
                $owner = \App\Models\User::find($data['user_id']);
                if (!$owner->hasRole('manager') && !$owner->hasRole('admin')) {
                    throw new HttpResponseException(
                        response()->json([
                            'success' => false,
                            'message' => [
                                'ar' => 'يجب أن يكون المستخدم المحدد مديرًا أو مسؤولًا.',
                                'en' => 'The specified user must be a manager or admin.',
                            ],
                        ], 422)
                    );
                }
                $hotel->user_id = $data['user_id'];
            } else {
                $hotel->user_id = auth()->id();
            }

            $hotel->save();

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $hotel->addMedia($image)
                        ->toMediaCollection('images');
                }
            }

            return $hotel;
        });

        return response()->json([
            'success' => true,
            'message' => [
                'ar' => 'تم إنشاء الفندق بنجاح.',
                'en' => 'Hotel created successfully.',
            ],
            'data' => [
                'hotel' => new HotelResource(
                    $hotel->load('user', 'city', 'media')
                ),
            ],
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
                'success' => false,
                'message' => [
                    'ar' => 'يوجد فندق آخر بنفس الاسم في هذه المدينة.',
                    'en' => 'Another hotel with the same name already exists in this city.',
                ],
                'data' => [
                    'existing_hotel' => new HotelResource(
                        $similarHotel->load('user', 'city')
                    ),
                ],
            ], 409);
        }

        DB::transaction(function () use ($data, $request, $hotel) {
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

                'facebook_url' =>
                $data['facebook_url'] ?? $hotel->facebook_url,

                'instagram_url' =>
                $data['instagram_url'] ?? $hotel->instagram_url,

                'star_rating' => $data['star_rating'] ?? $hotel->star_rating,
            ]);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $hotel->addMedia($image)
                        ->toMediaCollection('images');
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => [
                'ar' => 'تم تحديث الفندق بنجاح.',
                'en' => 'Hotel updated successfully.',
            ],
            'data' => [
                'hotel' => new HotelResource(
                    $hotel->fresh()->load('user', 'city', 'media')
                ),
            ],
        ]);
    }

    public function destroy(Hotel $hotel)
    {
        $this->authorizeHotelAccess($hotel);

        $hotel->delete();

        return response()->json([
            'success' => true,
            'message' => [
                'ar' => 'تم حذف الفندق بنجاح.',
                'en' => 'Hotel deleted successfully.',
            ],
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
            throw new HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => [
                        'ar' => 'غير مصرح لك بهذا الإجراء.',
                        'en' => 'You are not authorized to perform this action.',
                    ],
                ], 403)
            );
        }
    }

    public function transfer(
        TransferHotelRequest $request,
        Hotel $hotel
    ) {
        $newOwner = \App\Models\User::find($request->user_id);

        if (!$newOwner) {
            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'المستخدم غير موجود.',
                    'en' => 'User not found.',
                ],
            ], 404);
        }

        if (
            !$newOwner->hasRole('manager') &&
            !$newOwner->hasRole('admin')
        ) {
            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'يجب أن يكون المستخدم مديرًا أو مسؤولًا.',
                    'en' => 'User must be a manager or admin.',
                ],
            ], 422);
        }

        $hotel->update([
            'user_id' => $request->user_id
        ]);

        return response()->json([
            'success' => true,
            'message' => [
                'ar' => 'تم نقل ملكية الفندق بنجاح.',
                'en' => 'Hotel transferred successfully.',
            ],
            'data' => [
                'hotel' => new HotelResource(
                    $hotel->fresh()->load('user', 'city')
                ),
            ],
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
            'success' => true,
            'message' => [
                'ar' => 'تم رفع الصورة بنجاح.',
                'en' => 'Image uploaded successfully.',
            ],
            'data' => [
                'id' => $media->id,
                'url' => $media->getUrl(),
            ],
        ], 201);
    }

    public function getImages(Hotel $hotel)
    {
        $images = $hotel->getMedia('images')->map(fn($media) => [
            'id' => $media->id,
            'url' => $media->getUrl(),
            'name' => $media->file_name,
            'mime_type' => $media->mime_type,
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
                'success' => false,
                'message' => [
                    'ar' => 'الصورة غير موجودة.',
                    'en' => 'Image not found.',
                ],
            ], 404);
        }

        $media->delete();

        return response()->json([
            'success' => true,
            'message' => [
                'ar' => 'تم حذف الصورة بنجاح.',
                'en' => 'Image deleted successfully.',
            ],
        ]);
    }

    public function syncServices(Request $request, Hotel $hotel)
    {
        $this->authorizeHotelAccess($hotel);

        $validated = $request->validate([
            'service_ids' => 'required|array',
            'service_ids.*' => 'exists:services,id',
        ]);

        $hotel->services()->sync($validated['service_ids']);

        return $this->success(
            new HotelResource($hotel->load('user', 'city', 'services')),
            ['ar' => 'تم تحديث خدمات الفندق بنجاح.', 'en' => 'Hotel services updated successfully.']
        );
    }

    public function updateStatus(
        UpdateHotelStatusRequest $request,
        Hotel $hotel
    ) {
        $this->authorizeHotelAccess($hotel);

        $hotel->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        return response()->json([
            'success' => true,
            'message' => [
                'ar' => 'تم تحديث حالة الفندق بنجاح.',
                'en' => 'Hotel status updated successfully.',
            ],
            'data' => [
                'hotel' => new HotelResource(
                    $hotel->fresh()->load(
                        'user',
                        'city',
                        'services'
                    )
                ),
            ],
        ]);
    }
}
