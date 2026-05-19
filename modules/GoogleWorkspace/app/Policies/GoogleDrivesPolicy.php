<?php

namespace Modules\GoogleWorkspace\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\GoogleWorkspace\Models\GoogleDrives;
use Modules\Users\Models\User;

class GoogleDrivesPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any google drive.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the google drive.
     */
    public function view(User $user, GoogleDrives $drive): bool
    {
        if ($user->can('view all google drive')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the given user can create google drive.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the google drive.
     */
    public function delete(User $user, GoogleDrives $drive): bool
    {
        if ($user->can('delete any google drive')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user bulk delete assets.
     */
    public function bulkDelete(User $user, ?GoogleDrives $drive = null)
    {
        if (! $drive) {
            return $user->can('bulk delete google drive');
        }

        if ($user->can('bulk delete google drive')) {
            return $this->delete($user, $drive);
        }

        return false;
    }

}
