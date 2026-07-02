<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ArchiveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocationHistoryController extends BaseController
{
    public function __construct(
        private readonly ArchiveService $archiveService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'q' => $request->string('q')->toString() ?: null,
            'date_from' => $request->string('date_from')->toString() ?: null,
            'date_to' => $request->string('date_to')->toString() ?: null,
        ];

        $histories = $this->archiveService->getLocationHistories(Auth::user(), $filters);

        return $this->successResponse($histories, 'Riwayat lokasi berhasil diambil.');
    }
}
