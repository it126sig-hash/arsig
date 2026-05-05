<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CabinetSlot;
use App\Http\Requests\StoreCabinetSlotRequest;
use App\Http\Requests\UpdateCabinetSlotRequest;
use App\Http\Resources\CabinetSlotResource;

class CabinetSlotController extends Controller
{
    public function index()
    {
        $slots = CabinetSlot::with(['cabinet.room.floor', 'picUser'])->latest()->get();
        return CabinetSlotResource::collection($slots);
    }

    public function store(StoreCabinetSlotRequest $request)
    {
        $data = $request->validated();
        
        $slot = CabinetSlot::create($data);

        return new CabinetSlotResource($slot->load(['cabinet', 'picUser']));
    }

    public function show(CabinetSlot $cabinetSlot)
    {
        return new CabinetSlotResource($cabinetSlot->load(['cabinet', 'picUser']));
    }

    public function update(UpdateCabinetSlotRequest $request, CabinetSlot $cabinetSlot)
    {
        $data = $request->validated();

        $cabinetSlot->update($data);

        return new CabinetSlotResource($cabinetSlot->load(['cabinet', 'picUser']));
    }

    public function destroy(CabinetSlot $cabinetSlot)
    {
        $cabinetSlot->delete();

        return response()->noContent();
    }
}
