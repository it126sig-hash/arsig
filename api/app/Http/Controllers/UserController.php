<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserController extends BaseController
{
    public function __construct(
        private readonly UserService $service
    ) {}

    public function index(): JsonResponse
    {
        return $this->successResponse($this->service->getAll());
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->service->store($request->validated());
        return $this->successResponse($user->load('department'), 'User berhasil dibuat.', 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->service->update($user, $request->validated());
        return $this->successResponse($user->fresh()->load('department'), 'User berhasil diperbarui.');
    }

    public function destroy(User $user): JsonResponse
    {
        try {
            $this->service->destroy($user);
            return $this->successResponse(null, 'User berhasil dihapus.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
