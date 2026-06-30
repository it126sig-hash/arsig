<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $casts = [
        'id' => 'integer',
    ];

    protected $fillable = [
        'name',
    ];

    public function heads(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'department_heads')->withTimestamps();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
