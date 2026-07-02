<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Floor;
use App\Http\Requests\StoreFloorRequest;
use App\Http\Requests\UpdateFloorRequest;
use App\Http\Resources\FloorResource;
use Illuminate\Support\Facades\Storage;

class FloorController extends Controller
{
    public function index()
    {
        $floors = Floor::latest()->get();
        return FloorResource::collection($floors);
    }

    public function store(StoreFloorRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('floor_plan_image')) {
            $data['floor_plan_image'] = $request->file('floor_plan_image')->store('floors', 'public');
        }

        $floor = Floor::create($data);

        return new FloorResource($floor);
    }

    public function show(Floor $floor)
    {
        return new FloorResource($floor);
    }

    public function update(UpdateFloorRequest $request, Floor $floor)
    {
        $data = $request->validated();

        if ($request->hasFile('floor_plan_image')) {
            if ($floor->floor_plan_image) {
                Storage::disk('public')->delete($floor->floor_plan_image);
            }
            $data['floor_plan_image'] = $request->file('floor_plan_image')->store('floors', 'public');
        }

        $floor->update($data);

        return new FloorResource($floor);
    }

    public function destroy(Floor $floor)
    {
        if ($floor->floor_plan_image) {
            Storage::disk('public')->delete($floor->floor_plan_image);
        }
        $floor->delete();

        return response()->noContent();
    }

    public function trashed()
    {
        return FloorResource::collection(Floor::onlyTrashed()->latest()->get());
    }

    public function restore(int $id)
    {
        $floor = Floor::onlyTrashed()->findOrFail($id);
        $floor->restore();

        return new FloorResource($floor);
    }
}
