<?php
declare(strict_types=1);

namespace App\Policies;

use App\Models\Archive;
use App\Models\User;

class ArchivePolicy
{
    private function isPrivileged(User $user): bool
    {
        return in_array($user->role, ['root', 'admin'], true);
    }

    private function isDepartmentHeadForArchive(User $user, Archive $archive): bool
    {
        return (bool) $archive->pic?->department?->heads->contains('id', $user->id);
    }

    private function hasVerifiedOtpAccess(User $user, Archive $archive): bool
    {
        return \App\Models\ArchiveDownloadRequest::where('archive_id', $archive->id)
            ->where('requester_user_id', $user->id)
            ->where('status', 'approved')
            ->where('is_verified', true)
            ->where('otp_expires_at', '>', now())
            ->exists();
    }

    public function view(User $user, Archive $archive): bool
    {
        if ($archive->is_confidential) {
            return $this->isPrivileged($user)
                || (int) $archive->created_by === (int) $user->id
                || (int) $archive->pic_user_id === (int) $user->id
                || $this->isDepartmentHeadForArchive($user, $archive)
                || $this->hasVerifiedOtpAccess($user, $archive);
        }

        if ($archive->privacy_type === 'public') {
            return true;
        }

        if ($this->isPrivileged($user)) {
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
        return $this->hasVerifiedOtpAccess($user, $archive);
    }

    public function update(User $user, Archive $archive): bool
    {
        return (int) $user->id === (int) $archive->pic_user_id || $this->isPrivileged($user);
    }

    public function delete(User $user, Archive $archive): bool
    {
        return (int) $user->id === (int) $archive->pic_user_id || $this->isPrivileged($user);
    }

    public function viewLocation(User $user, Archive $archive): bool
    {
        if (! $archive->is_confidential) {
            return $this->view($user, $archive);
        }

        return $this->isPrivileged($user)
            || (int) $archive->pic_user_id === (int) $user->id
            || $this->isDepartmentHeadForArchive($user, $archive);
    }

    public function moveLocation(User $user, Archive $archive): bool
    {
        return $this->viewLocation($user, $archive);
    }
}
