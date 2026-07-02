<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CabinetSlot;
use App\Http\Requests\StoreCabinetSlotRequest;
use App\Http\Requests\UpdateCabinetSlotRequest;
use App\Http\Resources\CabinetSlotResource;

class CabinetSlotController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $slots = CabinetSlot::with(['cabinet.room.floor', 'picUsers', 'tags'])
            ->when($request->cabinet_id, fn($q) => $q->where('cabinet_id', $request->cabinet_id))
            ->latest()
            ->get();
        return CabinetSlotResource::collection($slots);
    }

    public function store(StoreCabinetSlotRequest $request)
    {
        $data = $request->validated();
        unset($data['pic_user_ids'], $data['tag_ids']);
        
        $slot = CabinetSlot::create($data);

        if ($request->has('pic_user_ids')) {
            $slot->picUsers()->sync($request->pic_user_ids);
        }
        if ($request->has('tag_ids')) {
            $slot->tags()->sync($request->tag_ids);
        }

        return new CabinetSlotResource($slot->load(['cabinet', 'picUsers', 'tags']));
    }

    public function show(CabinetSlot $cabinetSlot)
    {
        return new CabinetSlotResource($cabinetSlot->load(['cabinet', 'picUsers', 'tags']));
    }

    public function update(UpdateCabinetSlotRequest $request, CabinetSlot $cabinetSlot)
    {
        $data = $request->validated();
        unset($data['pic_user_ids'], $data['tag_ids']);

        $cabinetSlot->update($data);

        if ($request->has('pic_user_ids')) {
            $cabinetSlot->picUsers()->sync($request->pic_user_ids);
        }
        if ($request->has('tag_ids')) {
            $cabinetSlot->tags()->sync($request->tag_ids);
        }

        return new CabinetSlotResource($cabinetSlot->load(['cabinet', 'picUsers', 'tags']));
    }

    public function destroy(CabinetSlot $cabinetSlot)
    {
        $cabinetSlot->delete();

        return response()->noContent();
    }

    public function trashed()
    {
        return CabinetSlotResource::collection(
            CabinetSlot::onlyTrashed()->with(['cabinet.room.floor', 'picUsers', 'tags'])->latest()->get()
        );
    }

    public function restore(int $id)
    {
        $slot = CabinetSlot::onlyTrashed()->findOrFail($id);
        $slot->restore();

        return new CabinetSlotResource($slot->load(['cabinet', 'picUsers', 'tags']));
    }
}
