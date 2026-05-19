<?php

namespace Modules\Sms\Resources;

use Modules\Activities\Models\ActivityType;
use Modules\Core\Contracts\Resources\Tableable;
use Modules\Core\Contracts\Resources\WithResourceRoutes;
use Modules\Core\Fields\BelongsTo;
use Modules\Core\Resource\Resource;
use Modules\Sms\Http\Resources\SmsResource;
use Modules\Core\Http\Requests\ResourceRequest;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Contracts\Resources\Exportable;
use Modules\Core\Contracts\Resources\Importable;
use Modules\Core\Table\Table;
use Modules\Core\Fields\FieldsCollection;
use Modules\Core\Table\Column;
use Modules\Core\Fields\Text;
use Modules\Core\Fields\CreatedAt;
use Modules\Core\Fields\UpdatedAt;
use Modules\Core\Menu\MenuItem;
use Modules\Core\Filters\Text as TextFilter;
use Modules\Core\Http\Requests\ActionRequest;
use Modules\Core\Models\Model;
use Modules\Core\Filters\FilterChildGroup;
use Modules\Core\Filters\FilterGroups;
use Modules\Core\Filters\DateTime as DateTimeFilter;

class Sms extends Resource implements WithResourceRoutes, Tableable, Importable, Exportable
{
    public static string $model = 'Modules\Sms\Models\Sms';

    /**
     * Get the json resource that should be used for json response.
     */
    public function jsonResource(): string
    {
        return SmsResource::class;
    }

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return __('sms::sms.sms');
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return __('sms::sms.sms');
    }

    public function table(Builder $query, ResourceRequest $request, string $identifier): Table
    {
        return Table::make($query, $request, $identifier)
            ->withViews()
            ->orderBy('created_at', 'desc');
    }

    public function fields(ResourceRequest $request): array
    {
        return [
            Text::make('message', "SMS Message")
                ->creationRules('required')
                ->updateRules('filled')
                ->disableInlineEdit(),

            Text::make('status', "Status")
                ->creationRules('required')
                ->updateRules('filled')
                ->disableInlineEdit(),

            BelongsTo::make('activityType', ActivityType::class, __('sms::sms.activity_type')),

            CreatedAt::make()->hidden(),

            UpdatedAt::make()->hidden(),
        ];
    }

    public function filters(ResourceRequest $request): array
    {
        return [
            TextFilter::make('activity_type_id', __('sms::sms.sms'))->withoutNullOperators(),
        ];
    }

    public function actions(ResourceRequest $request): array
    {
        return [
            new \Modules\Core\Actions\BulkEditAction($this),

            \Modules\Core\Actions\DeleteAction::make()->canRun(
                function (ActionRequest $request, Model $model, int $total) {
                    return $request->user()->can($total > 1 ? 'bulkDelete' : 'delete', $model);
                }
            )->showInline(),
        ];
    }
}
