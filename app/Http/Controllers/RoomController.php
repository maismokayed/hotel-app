<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\Hotel;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Enums\RoomStatus;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::with('hotel')->get();
        return RoomResource::collection($rooms);
    }

    public function show(Room $room)
    {
        $room->load('hotel');
        return new RoomResource($room);
    }

    public function indexByHotel(Hotel $hotel)
    {
        $rooms = Room::with('hotel')
            ->where('hotel_id', $hotel->id)
            ->get();

        return RoomResource::collection($rooms);
    }

    public function roomTypes(Hotel $hotel)
    {
        $groups = Room::where('hotel_id', $hotel->id)
            ->where('status', RoomStatus::AVAILABLE->value)
            ->get()
            ->groupBy(fn($room) => $room->type->value);

        $data = $groups->map(function ($rooms) {
            $sample = $rooms->first();

            $roomWithImage = $rooms->first(fn($room) => $room->getMedia('images')->isNotEmpty());

            return [
                'type' => [
                    'value' => $sample->type->value,
                    'label' => $sample->type->label(),
                ],
                'price_per_night' => $sample->price_per_night,
                'capacity'        => $sample->capacity,
                'available_count' => $rooms->count(),
                'cover_image'     => $roomWithImage
                    ? $roomWithImage->getFirstMediaUrl('images')
                    : null,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function store(StoreRoomRequest $request)
    {
        $data = $request->validated();

        $hotel = Hotel::findOrFail($data['hotel_id']);
        $this->authorizeHotelAccess($hotel);

        if (!isset($data['status'])) {
            $data['status'] = RoomStatus::AVAILABLE->value;
        }

        $room = Room::create($data);

        if ($request->hasFile('image')) {
            $room->addMedia($request->file('image'))->toMediaCollection('images');
        }

        return new RoomResource($room);
    }

    public function update(UpdateRoomRequest $request, Room $room)
    {
        $this->authorizeHotelAccess($room->hotel);

        $data = $request->validated();
        unset($data['remove_image']);

        $room->update($data);

        if ($request->hasFile('image')) {
            $room->addMedia($request->file('image'))->toMediaCollection('images');
        } elseif ($request->boolean('remove_image')) {
            $room->clearMediaCollection('images');
        }

        return new RoomResource($room);
    }

    public function destroy(Room $room)
    {
        $this->authorizeHotelAccess($room->hotel);

        $room->delete();

        return response()->json([
            'message' => 'Room deleted successfully'
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
}
