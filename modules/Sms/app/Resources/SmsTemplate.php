<?php

namespace Modules\Sms\Resources;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Contracts\Resources\Exportable;
use Modules\Core\Contracts\Resources\Importable;
use Modules\Core\Contracts\Resources\Tableable;
use Modules\Core\Contracts\Resources\WithResourceRoutes;
use Modules\Core\Fields\CreatedAt;
use Modules\Core\Fields\Text;
use Modules\Core\Fields\Textarea;
use Modules\Core\Fields\UpdatedAt;
use Modules\Core\Http\Requests\ActionRequest;
use Modules\Core\Http\Requests\ResourceRequest;
use Modules\Core\Models\Model;
use Modules\Core\Resource\Resource;
use Modules\Core\Table\Column;
use Modules\Core\Table\Table;
use Modules\Sms\Http\Resources\SmsTemplateResource;

class SmsTemplate extends Resource implements WithResourceRoutes, Tableable, Importable, Exportable
{
    public static string $model = \Modules\Sms\Models\SmsTemplate::class;

    /**
     * Get the json resource that should be used for json response.
     */
    public function jsonResource(): string
    {
        return SmsTemplateResource::class;
    }

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return __('sms::sms.sms_templates');
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return __('sms::sms.sms_template');
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
            Text::make('name', __('sms::sms.fields.sms_templates.name'))
                ->required()
                ->disableInlineEdit()
                ->tapIndexColumn(fn(Column $column) => $column
                    ->width('300px')
                    ->route('/sms/{id}/edit')
                    ->primary()),

            Textarea::make('body', __('sms::sms.fields.sms_templates.body'))
                ->disableInlineEdit()
                ->creationRules('required')
                ->updateRules('filled'),

            CreatedAt::make()->hidden(),

            UpdatedAt::make()->hidden(),
        ];
    }

    public function actions(ResourceRequest $request): array
    {
        return [
            \Modules\Core\Actions\DeleteAction::make()->canRun(
                function (ActionRequest $request, Model $model, int $total) {
                    return $request->user()->can($total > 1 ? 'bulkDelete' : 'delete', $model);
                }
            )->showInline(),
        ];
    }

    public static function name(): string
    {
        return 'sms_templates';
    }
}
