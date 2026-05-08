<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveLocationLog extends Model
{
    protected $table = 'archive_location_logs';

    protected $fillable = [
        'archive_id',
        'user_id',
        'old_floor_id',
        'old_room_id',
        'old_cabinet_id',
        'old_cabinet_slot_id',
        'new_floor_id',
        'new_room_id',
        'new_cabinet_id',
        'new_cabinet_slot_id',
        'notes',
    ];

    public function archive(): BelongsTo
    {
        return $this->belongsTo(Archive::class);
    }

    public function movedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function oldFloor(): BelongsTo
    {
        return $this->belongsTo(Floor::class, 'old_floor_id');
    }

    public function oldRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'old_room_id');
    }

    public function oldCabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class, 'old_cabinet_id');
    }

    public function oldCabinetSlot(): BelongsTo
    {
        return $this->belongsTo(CabinetSlot::class, 'old_cabinet_slot_id');
    }

    public function newFloor(): BelongsTo
    {
        return $this->belongsTo(Floor::class, 'new_floor_id');
    }

    public function newRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'new_room_id');
    }

    public function newCabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class, 'new_cabinet_id');
    }

    public function newCabinetSlot(): BelongsTo
    {
        return $this->belongsTo(CabinetSlot::class, 'new_cabinet_slot_id');
    }
}
