<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Archive extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'category_id',
        'name',
        'keterangan',
        'file_number',
        'archive_type',
        'file_type',
        'privacy_type',
        'download_policy',
        'status',
        'pic_user_id',
        'file_path',
        'issue_date',
        'expire_date',
        'reminder_date',
        'floor_id',
        'room_id',
        'cabinet_id',
        'cabinet_slot_id',
        'created_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expire_date' => 'date',
        'reminder_date' => 'date',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function privacyTargets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ArchivePrivacyTarget::class);
    }

    public function accessDepartments(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'archive_privacy_targets', 'archive_id', 'department_id');
    }

    public function accessUsers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'archive_privacy_targets', 'archive_id', 'user_id');
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class);
    }

    public function cabinetSlot(): BelongsTo
    {
        return $this->belongsTo(CabinetSlot::class);
    }

    public function tags(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'archive_tags');
    }
}
