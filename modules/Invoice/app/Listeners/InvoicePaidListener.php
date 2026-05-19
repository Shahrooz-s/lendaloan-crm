<?php

namespace Modules\Invoice\Listeners;

use Modules\Core\Actions\ActionFields;
use Modules\Invoice\Enums\InvoiceStatusEnum;
use Modules\Invoice\Events\InvoicePaidEvent;
use Modules\Invoice\Models\Customer as CustomerModel;
use Modules\Invoice\Notifications\Customer\InvoicePaidNotification as InvoicePaidNotificationForCustomer;
use Modules\Invoice\Notifications\SalesAgent\InvoicePaidNotification as InvoicePaidNotificationForSalesAgent;
use Modules\Deals\Actions\ChangeDealStage;
use Modules\Deals\Events\DealMovedToStage;
use Modules\Deals\Models\Stage;
use Illuminate\Database\Eloquent\Model;

class InvoicePaidListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(InvoicePaidEvent $event): void
    {
        $invoices = collect([$event->invoice]);
        $invoice = $invoices->first();

        $invoice->loadMissing('deal.pipeline');
        $invoice->forceFill(['status' => InvoiceStatusEnum::PAID->value])->save();

        $pipeline = $invoice->deal?->pipeline;

        if (isset($pipeline)) {
            $stage = Stage::where(['pipeline_id' => $pipeline->id, 'name' => config('invoice.stage_name')])->orWhere(
                ['id' => $invoice->deal->stage_id]
            )->get();
            $newStage = $stage->where('pipeline_id', $pipeline->id)->where('name', config('invoice.stage_name'))->first(
            );
            $previousStage = $stage->Where('id', $invoice->deal->stage_id)->first();

            $action = new ChangeDealStage();
            $action->handle(collect([$invoice->deal]), new ActionFields(['stage_id' => $newStage->id]));

            DealMovedToStage::dispatch(
                $invoice->deal,
                $previousStage
            );
        }

        $this->notify($invoice);
    }

    public function notify(Model $model): void
    {
        if (!$model->relationLoaded('customer'))
            $model->load('customer');

        $customer = $model->customer;

        if ($customer)
        {
            $customerModel = new CustomerModel();
            $customerModel->fill($customer->toArray());
            $customerModel->id = $customer->id;

            $customerModel->notify(new InvoicePaidNotificationForCustomer($model, $customer));
        }

        if (!$model->relationLoaded('createdBy'))
            $model->load('createdBy');

        $user = $model->createdBy;
        if ($user)
            $user->notify(new InvoicePaidNotificationForSalesAgent($model, $customer));
    }
}
