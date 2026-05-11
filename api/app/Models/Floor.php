<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Floor extends Model
{
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
    ];

    protected $fillable = [
        'name',
        'floor_plan_image',
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
