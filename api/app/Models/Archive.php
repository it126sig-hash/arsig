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
        'file_number',
        'archive_type',
        'privacy_type',
        'download_policy',
        'status',
        'pic_user_id',
        'file_path',
        'issue_date',
        'expire_date',
        'reminder_date',
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
}
