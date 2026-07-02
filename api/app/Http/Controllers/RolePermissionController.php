<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\RolePermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RolePermissionController extends BaseController
{
    public function index(): JsonResponse
    {
        return $this->successResponse(RolePermission::orderBy('module')->orderBy('role')->get());
    }

    public function update(Request $request): JsonResponse
    {
        if (! in_array($request->user()->role, ['root', 'admin'], true)) {
            return $this->errorResponse('Anda tidak memiliki izin untuk mengubah matriks izin.', null, 403);
        }

        $data = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*.role' => ['required', 'in:admin,user'],
            'permissions.*.module' => ['required', 'string'],
            'permissions.*.can_view' => ['required', 'boolean'],
            'permissions.*.can_create' => ['required', 'boolean'],
            'permissions.*.can_update' => ['required', 'boolean'],
            'permissions.*.can_delete' => ['required', 'boolean'],
        ]);

        foreach ($data['permissions'] as $row) {
            RolePermission::updateOrCreate(
                ['role' => $row['role'], 'module' => $row['module']],
                [
                    'can_view' => $row['can_view'],
                    'can_create' => $row['can_create'],
                    'can_update' => $row['can_update'],
                    'can_delete' => $row['can_delete'],
                ]
            );
        }

        return $this->successResponse(RolePermission::orderBy('module')->orderBy('role')->get(), 'Matriks izin berhasil diperbarui.');
    }
}
