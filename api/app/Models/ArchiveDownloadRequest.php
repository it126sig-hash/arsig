<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveDownloadRequest extends Model
{
    protected $fillable = [
        'archive_id',
        'requester_user_id',
        'status',
        'otp_code',
        'otp_expires_at',
        'reviewed_by_user_id',
        'signed_url',
        'signed_url_expires_at',
        'is_verified',
    ];

    protected $casts = [
        'otp_expires_at' => 'datetime',
        'signed_url_expires_at' => 'datetime',
        'is_verified' => 'boolean',
    ];

    public function archive(): BelongsTo
    {
        return $this->belongsTo(Archive::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
