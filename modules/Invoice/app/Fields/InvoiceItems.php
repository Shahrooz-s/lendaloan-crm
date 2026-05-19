<?php

namespace Modules\Invoice\Fields;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Arr;
use Modules\Billable\Http\Resources\ProductResource;
use Modules\Billable\Resources\Product;
use Modules\Core\Facades\Innoclapps;
use Modules\Core\Fields\ConfiguresOptions;
use Modules\Core\Fields\Field;
use Modules\Core\Fields\Selectable;
use Modules\Core\Http\Requests\ResourceRequest;
use Modules\Core\Models\Model;
use Modules\Core\Support\HasOptions;
use Modules\Core\Table\MorphToManyColumn;
use Modules\Billable\Models\Product as ProductModel;
use Modules\Invoice\DTO\InvoiceItemDto;

class InvoiceItems extends Field
{
    use ConfiguresOptions, HasOptions, Selectable;

    protected static $component = 'select-multiple-field';

    public bool $excludeFromIndexQuery = true;

    public ?int $order = 999;

    public array $with = ['items'];

    protected bool $searchable = false;

    protected static Product $resource;

    /**
     * Create new instance of Products field.
     *
     * @param  string|null  $label
     */
    public function __construct($label = null)
    {
        parent::__construct('products', $label ?? __('billable::product.products'));

        static::$resource = Innoclapps::resourceByModel(ProductModel::class);

        $this->labelKey('name')
            ->valueKey('id')
            ->excludeFromExport()
            ->excludeFromImport()
            ->onOptionClick('float', ['resourceName' => static::$resource->name()])
            ->eachOnNewLine()
            ->excludeFromZapierResponse()
            ->async('/products/search')
            ->lazyLoad('/products', ['order' => 'created_at|desc'])
            ->provideSampleValueUsing(fn () => [1, 2])
            ->fillUsing(function (Model $model, string $attribute, ResourceRequest $request, mixed $value) {
                return ! is_null($value) ? $this->fillCallback($model, $this->parseValue($value, $request)) : null;
            })->resolveForJsonResourceUsing(function (Model $model, string $attribute) {
                if ($model->relationLoaded(static::$resource->associateableName())) {
                    return [
                        $attribute => ProductResource::collection($this->resolve($model)),
                    ];
                }
            });
    }

    /**
     * Provide the column used for index.
     */
    public function indexColumn(): MorphToManyColumn
    {
        return (new MorphToManyColumn(
            'items',
            $this->labelKey,
            $this->label
        ))->wrap();
    }

    public function mailableTemplatePlaceholder($model)
    {
        //
    }

    /**
     * Parse the given value for storage.
     */
    protected function parseValue($value, ResourceRequest $request): Collection
    {
        // Perhaps int e.q. when ID provided?
        $value = is_string($value) ? explode(',', $value) : Arr::wrap($value);
        $collection = new Collection([]);

        foreach ($value as $id) {
            if ($model = $this->getModelFromValue($id, $request)) {
                $collection->push($model);
            }
        }

        return $collection;
    }

    /**
     * Get model instance from the given ID and ensure it's authorized to view before syncing.
     */
    protected function getModelFromValue(int|string|null $value, ResourceRequest $request): ?EloquentModel
    {
        // ID provided?
        if (is_numeric($value)) {
            $model = static::$resource->newQuery()->find($value);
        } elseif ($value) {
            $model = static::$resource->newQueryWithTrashed()->where('name', trim($value))->first();

            if ($model?->trashed()) {
                $model->restore();
            }
        }

        return $model && $request->user()->can('view', $model) ? $model : null;
    }

    /**
     * Get the fill callback.
     */
    protected function fillCallback(Model $model, Collection $models)
    {
        return function () use ($model, $models) {
            if ($model->wasRecentlyCreated) {
                if (count($models) > 0) {
                    foreach ($models as $invoiceItem)
                    {
                        $invoiceDetail = InvoiceItemDto::fromProduct($invoiceItem)->toArray();
                        $model->items()->create($invoiceDetail);
                    }
                }
            }
        };
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'options' => [],
            'labelKey' => $this->labelKey,
            'valueKey' => $this->valueKey,
        ]);
    }
}
