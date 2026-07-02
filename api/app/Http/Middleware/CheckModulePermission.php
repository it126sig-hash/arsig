<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\RolePermission;
use App\Models\UserPermission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModulePermission
{
    public function handle(Request $request, Closure $next, string $module, ?string $action = null): Response
    {
        $action ??= match ($request->method()) {
            'GET' => 'view',
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'view',
        };

        $user = $request->user();

        if ($user->role === 'root') {
            return $next($request);
        }

        $override = UserPermission::where('user_id', $user->id)
            ->where('module', $module)
            ->first();

        $allowed = $override
            ? (bool) $override->{"can_{$action}"}
            : RolePermission::allows($user->role, $module, $action);

        if (! $allowed) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses modul ini.');
        }

        return $next($request);
    }
}
