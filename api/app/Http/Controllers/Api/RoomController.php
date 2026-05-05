<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Http\Resources\RoomResource;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::with('floor')->latest()->get();
        return RoomResource::collection($rooms);
    }

    public function store(StoreRoomRequest $request)
    {
        $data = $request->validated();
        
        $room = Room::create($data);

        return new RoomResource($room->load('floor'));
    }

    public function show(Room $room)
    {
        return new RoomResource($room->load('floor'));
    }

    public function update(UpdateRoomRequest $request, Room $room)
    {
        $data = $request->validated();

        $room->update($data);

        return new RoomResource($room->load('floor'));
    }

    public function destroy(Room $room)
    {
        $room->delete();

        return response()->noContent();
    }
}
