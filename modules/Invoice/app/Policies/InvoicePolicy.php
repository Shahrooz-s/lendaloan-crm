<?php

namespace Modules\Invoice\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\ModuleInit;
use Modules\Users\Models\User;

class InvoicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any deals.
     */
    public function viewAny(User $user): bool
    {
        $moduleVerificationService = new ModuleInit();
        $moduleVerificationService->handle('invoice');

        return true;
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->can('view all invoices')) {
            return true;
        }

        if ($user->can('view own invoices') && (int) $user->id === (int) $invoice->created_by) {
            return true;
        }

        if ($invoice->created_by && $user->can('view team invoices')) {
            return $user->managesAnyTeamsOf($invoice->created_by);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Invoice $invoice): bool
    {
        if ($user->can('edit all invoices')) {
            return true;
        }

        if ($user->can('edit own invoices') && (int) $user->id === (int) $invoice->created_by) {
            return true;
        }

        if ($invoice->created_by && $user->can('edit team invoices') && $user->managesAnyTeamsOf($invoice->created_by)) {
            return true;
        }

        return false;
    }


    public function delete(User $user, Invoice $invoice): bool
    {
        $moduleVerificationService = new ModuleInit();
        $moduleVerificationService->handle('invoice');

        if ($user->can('delete any deal')) {
            return true;
        }

        if ($user->can('delete own deals') && (int) $user->id === (int) $invoice->user_id) {
            return true;
        }

        if ($invoice->user_id && $user->can('delete team deals') && $user->managesAnyTeamsOf($invoice->user_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user bulk delete deals.
     */
    public function bulkDelete(User $user, ?Invoice $invoice = null)
    {
        $moduleVerificationService = new ModuleInit();
        $moduleVerificationService->handle('invoice');

        if (! $invoice) {
            return $user->can('bulk delete deals');
        }

        if ($invoice && $user->can('bulk delete deals')) {
            return $this->delete($user, $invoice);
        }

        return false;
    }

}
