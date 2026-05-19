<?php

namespace Modules\Invoice\Resources;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Billable\Models\Product;
use Modules\Contacts\Models\Contact as ContactModel;
use Modules\Core\Contracts\Resources\AcceptsCustomFields;
use Modules\Core\Fields\Boolean;
use Modules\Core\Settings\SettingsMenuItem;
use Modules\Invoice\Models\Customer as CustomerModel;
use Modules\Core\Actions\DeleteAction;
use Modules\Core\Contracts\Resources\Tableable;
use Modules\Core\Contracts\Resources\WithResourceRoutes;
use Modules\Core\Fields\CreatedAt;
use Modules\Core\Fields\Field;
use Modules\Core\Fields\FieldsCollection;
use Modules\Core\Fields\Numeric;
use Modules\Core\Fields\Text;
use Modules\Core\Http\Requests\ActionRequest;
use Modules\Core\Models\Model;
use Modules\Deals\Fields\Deal;
use Modules\Invoice\Actions\ChangeInvoiceStatus;
use Modules\Invoice\Cards\InvoicePaidByAgent;
use Modules\Invoice\Cards\InvoicePaidByDeal;
use Modules\Invoice\Enums\InvoiceStatusEnum;
use Modules\Invoice\Fields\Customer;
use Modules\Invoice\Fields\CustomTextField;
use Modules\Core\Fields\Date;
use Modules\Core\Fields\ID;
use Modules\Core\Fields\UpdatedAt;
use Modules\Core\Http\Requests\ResourceRequest;
use Modules\Core\Menu\MenuItem;
use Modules\Core\Resource\Resource;
use Modules\Core\Rules\StringRule;
use Modules\Core\Table\Column;
use Modules\Core\Table\Table;
use Modules\Invoice\Fields\InvoiceItems;
use Modules\Invoice\Http\Resources\InvoiceResource;
use Modules\Invoice\Notifications\Customer\NewInvoiceNotification;
use Modules\Invoice\Services\ModuleInitializationService;
use Modules\Invoice\Services\ModuleInit;

class Invoice extends Resource implements AcceptsCustomFields, Tableable, WithResourceRoutes {
    /**
     * The model the resource is related to.
     */
    public static string $model = 'Modules\Invoice\Models\Invoice';
    public static ?string $icon = 'CurrencyDollar';

    /**
     * Indicates whether the resource fields are customizeable
     */
    public static bool $fieldsCustomizable = true;

    /**
     * Get the json resource that should be used for json response.
     */
    public function jsonResource(): string
    {
        return InvoiceResource::class;
    }

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return __('invoice::invoice.invoices');
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return __('invoice::invoice.invoice');
    }

    public function table(Builder $query, ResourceRequest $request, string $identifier): Table
    {
        $moduleVerificationService = new ModuleInit();
        $moduleVerificationService->handle('invoice');

        if (!ModuleInitializationService::isModuleActive("invoice")) {
            abort(403, "Module is inactive. Please activate it via settings/invoices.");
        }

        return Table::make($query, $request, $identifier)
            ->withActionsColumn()
            ->withViews()
            ->withDefaultView(name: 'invoice::invoice.views.all', flag: 'all-invoices')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get the menu items for the resource
     */
    public function menu(): array
    {
        if (!ModuleInitializationService::isModuleActive("invoice")) {
            return [
                MenuItem::make(static::label(), '/settings/invoices', static::$icon)
                    ->icon(static::$icon)
                    ->position(55),
            ];
        }

        return [
            MenuItem::make(static::label(), '/invoices', static::$icon)
                ->icon(static::$icon)
                ->inQuickCreate()
                ->position(55),
        ];
    }

    /**
     * Get the fields for index.
     */
    public function fieldsForIndex(): FieldsCollection
    {
        return (new FieldsCollection([
            Text::make('invoice_number', __('invoice::fields.invoice_number'))
                ->searchable(true)
                ->disableInlineEdit()
                ->tapIndexColumn(fn (Column $column) => $column
//                    ->route('/invoices/{id}/edit')
                    ->primary()),

            Text::make('status', __('invoice::fields.status'))
                ->resolveUsing(fn ($model) => $model->status->name)
                ->displayUsing(fn ($model, $value) => InvoiceStatusEnum::find($value)->label())
                ->tapIndexColumn(function (Column $column) {
                    $column->centered()
                        ->withMeta([
                            'status' => collect(InvoiceStatusEnum::cases())->mapWithKeys(function ($status) {
                                return [$status->value => [
                                    'name' => $status->label(),
                                    'badge' => $status->badgeVariant(),
                                ]];
                            }),
                        ])
                        ->orderByUsing(function (Builder $query, string $direction) {
                            return $query->orderByRaw('CASE
                                WHEN status ="'.InvoiceStatusEnum::UNPAID->value.'" THEN 1
                                WHEN status ="'.InvoiceStatusEnum::PARTIALLY_PAID->value.'" THEN 2
                                WHEN status ="'.InvoiceStatusEnum::PAID->value.'" THEN 3
                            END '.$direction);
                        });
                }),

            Deal::make()->order(1)
                ->onOptionClick("float", ['resourceName' => 'deals'])
                ->tapIndexColumn(function (Column $column) {
                    $column
                        ->wrap()
                        ->queryAs('deals.name');
                }),

            Numeric::make('taxable_amount', __('invoice::fields.taxable_amount'))
                ->disableInlineEdit()
                ->order(2)
                ->currency(),

            Numeric::make('total_tax', __('invoice::fields.total_tax'))
                ->disableInlineEdit()
                ->order(3)
                ->currency(),

            Numeric::make('total', __('invoice::fields.total'))
                ->disableInlineEdit()
                ->order(4)
                ->currency(),

            Date::make('date', __('invoice::fields.date'))
                ->order(5),

            Customer::make()
                ->labelKey('display_name')
                ->tapIndexColumn(function (Column $column) {
                    $column
                        ->wrap()
                        ->queryAs(ContactModel::nameQueryExpression('display_name'))
                        ->fillRowDataUsing(function (array &$row, Model $model) use ($column) {
                            $relatedModel = $model->customer;
                            $row[$column->attribute] = $relatedModel
                                ? $column->toRowData($relatedModel)
                                : null;
                        });
                }),

            Date::make('due_date', __('invoice::fields.due_date'))->hidden(),

            CreatedAt::make()->hidden(),

            UpdatedAt::make()->hidden(),
        ]));
    }

    /**
     * Provides the resource available CRUD fields
     */
    public function fields(ResourceRequest $request): array
    {

        $latestInvoice = DB::table('invoices')
            ->select('invoice_number')
            ->orderBy('invoice_number', 'desc')
            ->first();

        $invoiceNumber = $latestInvoice
            ? sprintf('%08d', $latestInvoice->invoice_number + 1)
            : sprintf('%08d', 1);

        return [
            ID::make()->hidden(),

            CustomTextField::make('invoice_number', __('invoice::fields.invoice_number'))
                ->tapIndexColumn(fn (Column $column) => $column
                    ->width('300px')->minWidth('200px')
                    ->primary()
                    ->route(! $column->isForTrashedTable() ? '/invoices/{id}' : '')
                )
                ->prependText('INV-')
                // ->checkPossibleDuplicatesWith(
                //     '/invoices/search', ['search_fields' => 'invoice_number'], 'invoice::invoice.possible_duplicate'
                // )
                ->excludeFromDetail()
                ->withDefaultValue($invoiceNumber)
                ->rules([StringRule::make(), 'unique:invoices,invoice_number', 'regex:/^\d+$/'])
                ->creationRules('required')
                ->updateRules('filled')
                ->importRules('required')
                ->required()
                ->primary()
                ->order(0),

            Text::make('invoice_number', __('invoice::fields.invoice_number'))
                ->excludeFromCreate()
                ->primary(),

            Deal::make()
                ->order(1)
                ->rules(['required', 'exists:deals,id']),

            Customer::make()
                ->order(2)
                ->rules(['required', 'exists:contacts,id'])
                ->labelKey('guest_display_name')
                ->required(),

            Date::make('date', __('invoice::fields.date'))
                ->order(3)
                ->withDefaultValue(Carbon::now()->format('Y-m-d'))
                ->rules(['required', 'date'])
                ->width('half')
                ->required(true),

            Date::make('due_date', __('invoice::fields.due_date'))
                ->order(4)
                ->rules(['required', 'date'])
                ->withDefaultValue(Carbon::now()->addMonth()->format('Y-m-d'))
                ->width('half'),

            InvoiceItems::make()
                ->order(5)
                ->rules(['required', 'array', 'min:1'])
                ->required(),

            Boolean::make('should_send_notification', __('invoice::fields.send_invoice'))
                ->order(6)
                ->rules(['nullable', 'boolean'])
                ->withDefaultValue(false)
                ->primary()
                ->excludeFromExport(),

            CreatedAt::make()->hidden(),
            UpdatedAt::make()->hidden(),
        ];
    }

    public function create(Model $model, ResourceRequest $request): Model
    {
        if (!ModuleInitializationService::isModuleActive("invoice")) {
            abort(403, "Module is inactive. Please activate it via settings/invoices.");
        }

        $products = Product::whereIn('id', $request->input('products'))->get();
        $products->map(function (Product $product) {
            $product->tax_amount = $product->unit_price * $product->tax_rate / 100;
            $product->total = $product->unit_price + $product->tax_amount;
            return $product;
        });

        $request->merge([
            'product_models' => $products,
        ]);

        [$model, $callbacks] = $this->fillFields($model, $request);

        $this->beforeCreate($model, $request);

        $model->save();

        DB::afterCommit(function () use ($callbacks, $model, $request) {
            collect($callbacks)->each->__invoke($model, $request);
        });

        $this->afterCreate($model, $request);

        return $model;
    }

    public function afterCreate(Model $model, ResourceRequest $request): void
    {

        if ($request->input('should_send_notification')) {
            if (!$model->relationLoaded('customer'))
                $model->load('customer');

            $customer = $model->customer;
            $customerModel = new CustomerModel();
            $customerModel->fill($customer->toArray());
            $customerModel->id = $customer->id;

            $customerModel->notify(new NewInvoiceNotification($model, $customer));
            Log::info("NOTIFICATION SEND");
        }

    }

    /**
     * Fill the model from the given request.
     *
     * @param  ResourceRequest&\Modules\Core\Http\Requests\InteractsWithResourceFields  $request
     * @return array{\Modules\Core\Models\Model, array<int, callable>}
     */
    public function fillFields(Model $model, ResourceRequest $request): array
    {
        $callbacks = [];

        $request->toFields()->each(function (Field $field) use ($request, &$model, &$callbacks) {
            $callback = $field->fill(
                $model,
                $field->attribute,
                $request,
                $field->requestAttribute()
            );

            if (is_callable($callback)) {
                $callbacks[] = $callback;
            }
        });

        $products = $request->input('product_models');
        if ($products) {
            $model->taxable_amount = $products->sum('unit_price');
            $model->total = $products->sum('total');
            $model->total_tax = $products->sum('tax_amount');
        }
        unset($model->should_send_notification);

        return [$model, $callbacks];
    }

    /**
     * Update resource record in storage.
     */
    public function update(Model $model, ResourceRequest $request): Model
    {
        if (!ModuleInitializationService::isModuleActive("invoice")) {
            abort(403, "Module is inactive. Please activate it via settings/invoices.");
        }

        [$model, $callbacks] = $this->fillFields($model, $request);

        $this->beforeUpdate($model, $request);

        $model->save();

        DB::afterCommit(function () use ($callbacks, $model, $request) {
            collect($callbacks)->each->__invoke($model, $request);
        });

        $this->afterUpdate($model, $request);

        return $model;
    }

    /**
     * Delete resource record.
     */
    public function delete(Model $model, ResourceRequest $request): bool
    {
        if (!ModuleInitializationService::isModuleActive("invoice")) {
            abort(403, "Module is inactive. Please activate it via settings/invoices.");
        }

        $this->beforeDelete($model, $request);

        $model->delete();

        $this->afterDelete($model, $request);

        return true;
    }

    /**
     * Provides the resource available actions
     */
    public function actions(ResourceRequest $request): array
    {
        if (!ModuleInitializationService::isModuleActive("invoice")) {
            return [];
        }

        return [
            new \Modules\Core\Actions\SearchInGoogleAction,

            DeleteAction::make()->canRun(function (ActionRequest $request, Model $model, int $total) {
                return $request->user()->can($total > 1 ? 'bulkDelete' : 'delete', $model);
            })->showInline()->withSoftDeletes(),

            ChangeInvoiceStatus::make()->showInline(),
        ];
    }

    /**
     * Get resource available cards
     */
    public function cards(): array
    {
        if (!ModuleInitializationService::isModuleActive("invoice")) {
            return [];
        }

        return [

            (new InvoicePaidByAgent)->canSee(function ($request) {
                return $request->user()?->canAny(['view all invoices', 'view team invoices']);
            })
                ->onlyOnDashboard(),

            (new InvoicePaidByDeal())->canSee(function ($request) {
                return $request->user()?->canAny(['view all invoices', 'view team invoices']);
            })
                ->onlyOnDashboard(),
        ];
    }

    public function settingsMenu() : array
    {
        $version = \Modules\Core\Application::VERSION;

        if ($version == '1.5.0') {
            return [
                SettingsMenuItem::make( __('invoice::invoice.invoice'), '/settings/invoices')
                    ->icon('CurrencyDollar')
                    ->order(41)
            ];
        } else {
            return [
                SettingsMenuItem::make('invoices', __('invoice::invoice.invoice'))
                    ->path('/invoices')
                    ->icon('CurrencyDollar')
                    ->order(41)
            ];
        }
    }
}
