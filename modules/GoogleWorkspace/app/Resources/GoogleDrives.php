<?php

namespace Modules\GoogleWorkspace\Resources;

use Modules\Core\Contracts\Resources\WithResourceRoutes;
use Modules\Core\Resource\Resource;
use Modules\GoogleWorkspace\Http\Resources\GoogleDrivesResource;
use Modules\Core\Contracts\Resources\Tableable;
use Modules\Core\Http\Requests\ResourceRequest;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Table\Table;
use Modules\Core\Fields\Text;
use Modules\Core\Fields\CreatedAt;
use Modules\Core\Fields\UpdatedAt;
use Modules\Core\Fields\FieldsCollection;
use Modules\GoogleWorkspace\Actions\CreateGoogleDriveFileAction;


class GoogleDrives extends Resource implements WithResourceRoutes, Tableable
{
    public static string $model = 'Modules\GoogleWorkspace\Models\GoogleDrives';

    public function jsonResource(): string
    {
        return GoogleDrivesResource::class;
    }

    public static function label(): string
    {
        return __('googleworkspace::google.google_drive');
    }

    public static function singularLabel(): string
    {
        return __('googleworkspace::google.google_drive');
    }

    public function table(Builder $query, ResourceRequest $request, string $identifier): Table
    {
        return Table::make($query, $request, $identifier)
            ->withViews()
            ->withDefaultView(
                name: 'googleworkspace::google.google_google_drive_default_view',
                flag: 'all-drives',
            )
            ->orderBy('created_at', 'desc');
    }

    public function fields(ResourceRequest $request): array
    {
        return [
            Text::make('name', __('googleworkspace::google.name'))->creationRules('required')->updateRules('filled'),
            Text::make('description', __('googleworkspace::google.description'))->creationRules('nullable')->updateRules('filled'),
            Text::make('is_available', __('googleworkspace::google.actions'))->creationRules('nullable')->updateRules('filled'),
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
            new CreateGoogleDriveFileAction(),
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
