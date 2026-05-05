<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'floor_id',
        'name',
        'points',
        'needs_coordinate_review',
    ];

    protected $casts = [
        'points' => 'array',
        'needs_coordinate_review' => 'boolean',
    ];

    public function floor()
    {
        return $this->belongsTo(Floor::class);
    }

    public function cabinets()
    {
        return $this->hasMany(Cabinet::class);
    }
}
