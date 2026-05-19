<?php

namespace Modules\Invoice\Cards;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Core\Card\TableCard;
use Modules\Invoice\Models\Invoice;
use Modules\Users\Models\User;

class InvoicePaidByAgent extends TableCard {
    /**
     * Provide the table items.
     *
     * @return \Illuminate\Support\Collection
     */
    public function items(Request $request): iterable
    {
        $range = $this->getCurrentRange($request);
        $startingDate = $this->getStartingDate($range, static::BY_MONTHS);
        $endingDate = Carbon::asAppTimezone();

        /** @var \Modules\Users\Models\User */
        $currentUser = Auth::user();

        return Invoice::select([
            (new User())->getTable() . '.name as name',
            DB::raw('SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid_count'),
            DB::raw('SUM(CASE WHEN status = "paid" THEN total ELSE 0 END) as paid_amount'),
            DB::raw('SUM(total) as forecasted_amount')
        ])
            ->join((new User())->getTable(), 'invoices.created_by', '=', (new User())->getTable() . '.id')
            ->whereBetween( (new Invoice())->getTable() . '.created_at', [$startingDate, $endingDate])
            ->when(
                $currentUser->cant('view all invoices'), fn(Builder $query) => $query->ofManager($currentUser)
            )
            ->groupBy('created_by', (new User())->getTable() . '.name')
            ->get()
            ->map(fn(Invoice $invoice) => [
            'name' => $invoice->name,
            'created_invoices_count' => $invoice->paid_count,
            'forecast_amount' => to_money($invoice->forecasted_amount)->format(),
            'closed_amount' => to_money($invoice->paid_amount)->format(),
        ]);
    }

    /**
     * Provide the table fields
     */
    public function fields(): array
    {

        return [
            ['key' => 'name', 'label' => __('users::user.sales_agent')],
            ['key' => 'created_invoices_count', 'label' => __('deals::deal.total_created')],
            ['key' => 'forecast_amount', 'label' => __('deals::deal.forecast_amount')],
            ['key' => 'closed_amount', 'label' => __('deals::deal.closed_amount')],
        ];
    }

    /**
     * Card title
     */
    public function name(): string
    {
        return __('invoice::invoice.cards.invoice_paid_by_agents');
    }

    /**
     * Get the ranges available for the chart.
     */
    public function ranges(): array
    {
        return [
            3 => __('core::dates.periods.last_3_months'),
            6 => __('core::dates.periods.last_6_months'),
            12 => __('core::dates.periods.last_12_months'),
        ];
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'helpText' => __('invoice::invoice.cards.invoice_paid_by_sale_agent_info'),
        ]);
    }
}
