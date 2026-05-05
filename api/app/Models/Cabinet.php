<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cabinet extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'name',
        'points',
        'needs_coordinate_review',
    ];

    protected $casts = [
        'points' => 'array',
        'needs_coordinate_review' => 'boolean',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function cabinetSlots()
    {
        return $this->hasMany(CabinetSlot::class);
    }
}
