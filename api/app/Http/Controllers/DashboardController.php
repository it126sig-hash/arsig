<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Archive;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class DashboardController extends BaseController
{
    public function getStats(): JsonResponse
    {
        $total = Archive::count();
        $borrowed = Archive::where('is_checked_out', true)->count();
        $inCabinet = $total - $borrowed;
        
        // Expiring: based on reminder_date being reached or expire_date within next 30 days
        $expiring = Archive::whereNotNull('expire_date')
            ->where(function ($query) {
                $query->whereDate('reminder_date', '<=', Carbon::now())
                    ->orWhereDate('expire_date', '<=', Carbon::now()->addDays(30));
            })
            ->count();

        return $this->successResponse([
            'total' => $total,
            'inCabinet' => $inCabinet,
            'borrowed' => $borrowed,
            'expiring' => $expiring,
        ], 'Statistik dashboard berhasil diambil.');
    }
}
