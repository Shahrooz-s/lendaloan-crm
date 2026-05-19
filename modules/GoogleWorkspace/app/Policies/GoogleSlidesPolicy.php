<?php

namespace Modules\GoogleWorkspace\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\GoogleWorkspace\Models\GoogleSlides;
use Modules\Users\Models\User;

class GoogleSlidesPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any google sheet.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the google sheet.
     */
    public function view(User $user, GoogleSlides $sheet): bool
    {
        if ($user->can('view all google sheet')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the given user can create google sheet.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the google sheet.
     */
    public function delete(User $user, GoogleSlides $sheet): bool
    {
        if ($user->can('delete any google sheet')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user bulk delete google sheet.
     */
    public function bulkDelete(User $user, ?GoogleSlides $sheet = null)
    {
        if (! $sheet) {
            return $user->can('bulk delete google sheet');
        }

        if ($user->can('bulk delete google sheet')) {
            return $this->delete($user, $sheet);
        }

        return false;
    }
}
