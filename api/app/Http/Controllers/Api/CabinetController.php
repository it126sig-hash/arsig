<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cabinet;
use App\Http\Requests\StoreCabinetRequest;
use App\Http\Requests\UpdateCabinetRequest;
use App\Http\Resources\CabinetResource;

class CabinetController extends Controller
{
    public function index()
    {
        $cabinets = Cabinet::with(['room.floor', 'cabinetSlots'])->latest()->get();
        return CabinetResource::collection($cabinets);
    }

    public function store(StoreCabinetRequest $request)
    {
        $data = $request->validated();
        
        $cabinet = Cabinet::create($data);

        return new CabinetResource($cabinet->load('room'));
    }

    public function show(Cabinet $cabinet)
    {
        return new CabinetResource($cabinet->load(['room', 'cabinetSlots.picUsers', 'cabinetSlots.tags']));
    }

    public function update(UpdateCabinetRequest $request, Cabinet $cabinet)
    {
        $data = $request->validated();

        $cabinet->update($data);

        return new CabinetResource($cabinet->load('room'));
    }

    public function destroy(Cabinet $cabinet)
    {
        $cabinet->delete();

        return response()->noContent();
    }
}
