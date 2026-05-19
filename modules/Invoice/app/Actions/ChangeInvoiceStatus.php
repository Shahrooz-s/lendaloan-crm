<?php

namespace Modules\Invoice\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Core\Actions\Action;
use Modules\Core\Actions\ActionFields;
use Modules\Core\Fields\Select;
use Modules\Core\Http\Requests\ActionRequest;
use Modules\Core\Http\Requests\ResourceRequest;
use Modules\Invoice\Enums\InvoiceStatusEnum;
use Modules\Invoice\Events\InvoicePaidEvent;

class ChangeInvoiceStatus extends CustomAction {
    /**
     * Indicates that the action will be shown on the detail view.
     */
    public bool $showOnDetail = false;

    /**
     * Handle method.
     */
    public function handle(Collection $models, ActionFields $fields): void
    {
        foreach ($models as $model)
        {
            if ($model->status->value == $fields->status) {
                continue;
            }

            $model->loadMissing('deal.pipeline');
            $model->forceFill(['status' => $fields->status])->save();

            if ($model->status == InvoiceStatusEnum::PAID) {
                InvoicePaidEvent::dispatch($model);
            }
        }
    }

    /**
     * Get the action fields.
     */
    public function fields(ResourceRequest $request): array
    {
        return [
            Select::make('status', __('invoice::fields.status'))
                ->rules('required')
                ->options(function () use ($request) {
                    return InvoiceStatusEnum::toArray();
                }),
        ];
    }

    /**
     * @param Model $model
     */
    public function authorizedToRun(ActionRequest $request, $model): bool
    {
        return $request->user()->can('update', $model);
    }


    /**
     * Action name.
     */
    public function name(): string
    {
        return __('invoice::invoice.actions.change_status');
    }

}
