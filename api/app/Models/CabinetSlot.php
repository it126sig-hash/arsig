<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CabinetSlot extends Model
{
    use HasFactory, SoftDeletes;

    protected $casts = [
        'id' => 'integer',
        'cabinet_id' => 'integer',
    ];

    protected $fillable = [
        'cabinet_id',
        'name',
        'keterangan',
        'status',
    ];

    public function cabinet()
    {
        return $this->belongsTo(Cabinet::class);
    }

    public function picUsers()
    {
        return $this->belongsToMany(User::class, 'cabinet_slot_user');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'cabinet_slot_tag');
    }
}
