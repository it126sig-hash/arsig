<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CabinetSlotTag extends Model
{
    protected $fillable = ['cabinet_slot_id', 'tag'];

    public function cabinetSlot()
    {
        return $this->belongsTo(CabinetSlot::class);
    }
}
