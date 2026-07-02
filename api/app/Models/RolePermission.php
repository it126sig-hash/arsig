<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $fillable = [
        'role', 'module', 'can_view', 'can_create', 'can_update', 'can_delete',
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_create' => 'boolean',
        'can_update' => 'boolean',
        'can_delete' => 'boolean',
    ];

    public static function allows(string $role, string $module, string $action): bool
    {
        if ($role === 'root') {
            return true;
        }

        return (bool) static::query()
            ->where('role', $role)
            ->where('module', $module)
            ->value("can_{$action}");
    }
}
