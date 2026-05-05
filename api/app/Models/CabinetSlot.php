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
        'pic_user_id',
    ];

    public function cabinet()
    {
        return $this->belongsTo(Cabinet::class);
    }

    public function picUser()
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }
}
