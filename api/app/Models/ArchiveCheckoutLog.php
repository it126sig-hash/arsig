<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveCheckoutLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'archive_id',
        'actor_user_id',
        'action',
        'borrower_name',
        'reason',
        'planned_return_date',
        'actual_return_date',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'planned_return_date' => 'date',
        'actual_return_date' => 'date',
        'created_at' => 'datetime',
    ];

    public function archive(): BelongsTo
    {
        return $this->belongsTo(Archive::class);
    }

    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
