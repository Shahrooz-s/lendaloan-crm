<?php

namespace Modules\GoogleWorkspace\Resources;

use Modules\Core\Contracts\Resources\WithResourceRoutes;
use Modules\Core\Resource\Resource;
use Modules\GoogleWorkspace\Http\Resources\GoogleSheetsResource;
use Modules\Core\Contracts\Resources\Tableable;
use Modules\Core\Http\Requests\ResourceRequest;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Table\Table;
use Modules\Core\Fields\Text;
use Modules\Core\Fields\CreatedAt;
use Modules\Core\Fields\UpdatedAt;
use Modules\Core\Fields\FieldsCollection;


class GoogleSheets extends Resource implements WithResourceRoutes, Tableable
{
    public static string $model = 'Modules\GoogleWorkspace\Models\GoogleSheets';

    public function jsonResource(): string
    {
        return GoogleSheetsResource::class;
    }

    public static function label(): string
    {
        return __('googleworkspace::google.google_sheets');
    }

    public static function singularLabel(): string
    {
        return __('googleworkspace::google.google_sheets');
    }

    public function table(Builder $query, ResourceRequest $request, string $identifier): Table
    {
        return Table::make($query, $request, $identifier)
            ->withViews()
            ->withDefaultView(
                name: 'googleworkspace::google.google_sheets_default_view',
                flag: 'all-sheets',
            )
            ->orderBy('created_at', 'desc');
    }

    public function fields(ResourceRequest $request): array
    {
        return [
            Text::make('name', __('googleworkspace::google.name'))->creationRules('required')->updateRules('filled'),
            CreatedAt::make()->hidden(),
            UpdatedAt::make()->hidden(),
        ];
    }

    public function fieldsForIndex(): FieldsCollection
    {
        return new FieldsCollection([
            Text::make('name', __('googleworkspace::google.name')),
            Text::make('is_available', __('googleworkspace::google.actions')),
            CreatedAt::make()->hidden(),
            UpdatedAt::make()->hidden(),
        ]);
    }

    public function actions(ResourceRequest $request): array
    {
        return [
            new \Modules\GoogleWorkspace\Actions\CreateGoogleSheetAction(),
        ];
    }

    /**
     * Register permissions for the resource
     */
    public function registerPermissions(): void
    {
        $this->registerCommonPermissions();
    }
}
