<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CabinetSlot extends Model
{
    use HasFactory;

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
