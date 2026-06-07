<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Enums\RoomStatus;

class RoomController extends Controller
{
    public function index()
{
    $rooms = Room::with('hotel')->get();

    return response()->json($rooms);
}
public function show(Room $room)
{
    $room->load('hotel');

    return response()->json($room);
}

    public function store(StoreRoomRequest $request)
{
    $data = $request->validated();

    if (!isset($data['status'])) {
        $data['status'] = RoomStatus::AVAILABLE->value;
    }

    $room = Room::create($data);
    return response()->json($room, 201);
}

public function update(UpdateRoomRequest $request, Room $room)
{
    $data = $request->validated();
    $room->update($data);
    return response()->json($room);
}
public function destroy(Room $room)
{
    $room->delete();

    return response()->json([
        'message' => 'Room deleted successfully'
    ]);
}

}
