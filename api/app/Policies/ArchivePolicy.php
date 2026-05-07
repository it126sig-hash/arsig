<?php
declare(strict_types=1);

namespace App\Policies;

use App\Models\Archive;
use App\Models\User;

class ArchivePolicy
{
    public function view(User $user, Archive $archive): bool
    {
        if ($archive->privacy_type === 'public') {
            return true;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ((int) $archive->created_by === (int) $user->id) {
            return true;
        }

        if ((int) $archive->pic_user_id === (int) $user->id) {
            return true;
        }

        if ($archive->privacy_type === 'department') {
            if ($archive->accessDepartments()->where('departments.id', $user->department_id)->exists()) {
                return true;
            }
        }

        if ($archive->privacy_type === 'user') {
            if ($archive->accessUsers()->where('users.id', $user->id)->exists()) {
                return true;
            }
        }

        // Check for verified OTP access
        return \App\Models\ArchiveDownloadRequest::where('archive_id', $archive->id)
            ->where('requester_user_id', $user->id)
            ->where('status', 'approved')
            ->where('is_verified', true)
            ->where('otp_expires_at', '>', now())
            ->exists();
    }

    public function update(User $user, Archive $archive): bool
    {
        return (int) $user->id === (int) $archive->pic_user_id || $user->role === 'admin';
    }

    public function delete(User $user, Archive $archive): bool
    {
        return (int) $user->id === (int) $archive->pic_user_id || $user->role === 'admin';
    }
}
