<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cabinet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'room_id',
        'name',
        'keterangan',
        'door_count',
        'points',
        'needs_coordinate_review',
    ];

    protected $casts = [
        'id' => 'integer',
        'room_id' => 'integer',
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
