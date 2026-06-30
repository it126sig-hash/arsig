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
        'requires_department_approval',
        'approval_stage',
        'otp_code',
        'otp_expires_at',
        'reviewed_by_user_id',
        'pic_approved_by_user_id',
        'pic_approved_at',
        'department_approved_by_user_id',
        'department_approved_at',
        'rejected_by_user_id',
        'rejected_at',
        'signed_url',
        'signed_url_expires_at',
        'is_verified',
    ];

    protected $casts = [
        'requires_department_approval' => 'boolean',
        'otp_expires_at' => 'datetime',
        'pic_approved_at' => 'datetime',
        'department_approved_at' => 'datetime',
        'rejected_at' => 'datetime',
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

    public function picApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_approved_by_user_id');
    }

    public function departmentApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'department_approved_by_user_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_user_id');
    }
}
