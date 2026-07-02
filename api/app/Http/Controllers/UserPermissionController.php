<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPermissionController extends BaseController
{
    private const MODULES = [
        'companies', 'departments', 'floors', 'rooms',
        'cabinets', 'cabinet_slots', 'categories', 'tags', 'users',
    ];

    public function mine(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'root') {
            return $this->successResponse([]);
        }

        $roleRows    = RolePermission::where('role', $user->role)->get()->keyBy('module');
        $overrides   = UserPermission::where('user_id', $user->id)->get()->keyBy('module');

        $result = [];
        foreach (self::MODULES as $mod) {
            $source = $overrides[$mod] ?? $roleRows[$mod] ?? null;
            $result[$mod] = $source
                ? [
                    'can_view'   => (bool) $source->can_view,
                    'can_create' => (bool) $source->can_create,
                    'can_update' => (bool) $source->can_update,
                    'can_delete' => (bool) $source->can_delete,
                ]
                : ['can_view' => false, 'can_create' => false, 'can_update' => false, 'can_delete' => false];
        }

        return $this->successResponse($result);
    }

    public function show(User $user): JsonResponse
    {
        $roleRows  = RolePermission::where('role', $user->role)->get()->keyBy('module');
        $overrides = UserPermission::where('user_id', $user->id)->get()->keyBy('module');

        $result = [];
        foreach (self::MODULES as $mod) {
            $isCustom = isset($overrides[$mod]);
            $source   = $overrides[$mod] ?? $roleRows[$mod] ?? null;
            $result[] = [
                'module'     => $mod,
                'can_view'   => $source ? (bool) $source->can_view   : false,
                'can_create' => $source ? (bool) $source->can_create : false,
                'can_update' => $source ? (bool) $source->can_update : false,
                'can_delete' => $source ? (bool) $source->can_delete : false,
                'is_custom'  => $isCustom,
            ];
        }

        return $this->successResponse($result);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'permissions'              => ['required', 'array', 'size:' . count(self::MODULES)],
            'permissions.*.module'     => ['required', 'string', 'in:' . implode(',', self::MODULES)],
            'permissions.*.can_view'   => ['required', 'boolean'],
            'permissions.*.can_create' => ['required', 'boolean'],
            'permissions.*.can_update' => ['required', 'boolean'],
            'permissions.*.can_delete' => ['required', 'boolean'],
        ]);

        UserPermission::where('user_id', $user->id)->delete();

        foreach ($data['permissions'] as $row) {
            UserPermission::create([
                'user_id'    => $user->id,
                'module'     => $row['module'],
                'can_view'   => $row['can_view'],
                'can_create' => $row['can_create'],
                'can_update' => $row['can_update'],
                'can_delete' => $row['can_delete'],
            ]);
        }

        return $this->successResponse(null, 'Izin khusus user berhasil disimpan.');
    }

    public function reset(User $user): JsonResponse
    {
        UserPermission::where('user_id', $user->id)->delete();

        return $this->successResponse(null, 'Izin khusus user telah direset ke default role.');
    }
}
