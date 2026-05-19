<?php

namespace Modules\Invoice\Actions;

use Modules\Core\Actions\Action;

class CustomAction extends Action
{
    /**
     * Get the action modal confirmation component.
     */
    public function component(): string
    {
        return 'custom-action-modal';
    }

}
