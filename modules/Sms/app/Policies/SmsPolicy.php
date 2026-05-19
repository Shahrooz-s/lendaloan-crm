<?php

namespace Modules\Sms\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Sms\Models\Sms;
use Modules\Users\Models\User;

class SmsPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function view(User $user, Sms $sms): bool
    {
        return true;
        
        if ($user->can('view all sms')) {
            return true;
        }

        if ((int) $sms->user_id === (int) $user->id) {
            return true;
        }

        if ($sms->user_id && $user->can('view team sms')) {
            return $user->managesAnyTeamsOf($sms->user_id);
        }

        return false;
    }
}
