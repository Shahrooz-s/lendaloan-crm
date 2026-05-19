<?php

namespace Modules\GoogleWorkspace\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\GoogleWorkspace\Models\GoogleDocs;
use Modules\Users\Models\User;

class GoogleDocsPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any google docs.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the google docs.
     */
    public function view(User $user, GoogleDocs $docs): bool
    {
        if ($user->can('view all google docs')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the given user can create google docs.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the google docs.
     */
    public function delete(User $user, GoogleDocs $docs): bool
    {
        if ($user->can('delete any google docs')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user bulk delete assets.
     */
    public function bulkDelete(User $user, ?GoogleDocs $docs = null)
    {
        if (! $docs) {
            return $user->can('bulk delete google Docs');
        }

        if ($user->can('bulk delete google Docs')) {
            return $this->delete($user, $docs);
        }

        return false;
    }

}
