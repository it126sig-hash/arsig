<?php

namespace App\Observers;

use App\Models\Cabinet;
use App\Models\CabinetSlot;

class CabinetObserver
{
    public function created(Cabinet $cabinet): void
    {
        $this->generateSlots($cabinet);
    }

    public function updated(Cabinet $cabinet): void
    {
        if ($cabinet->isDirty('door_count')) {
            // Delete existing slots and regenerate
            $cabinet->cabinetSlots()->delete();
            $this->generateSlots($cabinet);
        }
    }

    private function generateSlots(Cabinet $cabinet): void
    {
        if (!$cabinet->door_count) return;

        $parts = explode('*', $cabinet->door_count);
        if (count($parts) !== 2) return;

        $cols = (int) trim($parts[0]);
        $rows = (int) trim($parts[1]);
        $total = $cols * $rows;

        if ($total <= 0 || $total > 999) return;

        $slots = [];
        for ($i = 1; $i <= $total; $i++) {
            $slots[] = [
                'cabinet_id' => $cabinet->id,
                'name' => str_pad($i, 2, '0', STR_PAD_LEFT),
                'status' => 'aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        CabinetSlot::insert($slots);
    }
}
