<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchivePrivacyTarget extends Model
{
    protected $fillable = [
        'archive_id',
        'user_id',
        'department_id',
    ];

    public function archive(): BelongsTo
    {
        return $this->belongsTo(Archive::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
