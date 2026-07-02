<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function getAll(): Collection
    {
        return User::with('department')->orderBy('name')->get();
    }

    public function store(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        return User::create($data);
    }

    public function update(User $user, array $data): bool
    {
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if (array_key_exists('role', $data) && $user->role !== $data['role']) {
            \App\Models\UserPermission::where('user_id', $user->id)->delete();
        }

        return $user->update($data);
    }

    public function destroy(User $user): bool
    {
        return $user->delete();
    }
}
