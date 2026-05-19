<?php

namespace Modules\GoogleWorkspace\Resources;

use Modules\Core\Contracts\Resources\WithResourceRoutes;
use Modules\Core\Resource\Resource;
use Modules\GoogleWorkspace\Http\Resources\GoogleDocsResource;
use Modules\Core\Contracts\Resources\Tableable;
use Modules\Core\Http\Requests\ResourceRequest;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Table\Table;
use Modules\Core\Fields\Text;
use Modules\Core\Fields\CreatedAt;
use Modules\Core\Fields\UpdatedAt;
use Modules\Core\Fields\FieldsCollection;
use Modules\GoogleWorkspace\Actions\CreateGoogleDocAction;


class GoogleDocs extends Resource implements WithResourceRoutes, Tableable
{
    public static string $model = 'Modules\GoogleWorkspace\Models\GoogleDocs';

    public function jsonResource(): string
    {
        return GoogleDocsResource::class;
    }

    public static function label(): string
    {
        return __('googleworkspace::google.google_docs');
    }

    public static function singularLabel(): string
    {
        return __('googleworkspace::google.google_docs');
    }

    public function table(Builder $query, ResourceRequest $request, string $identifier): Table
    {
        return Table::make($query, $request, $identifier)
            ->withViews()
            ->withDefaultView(
                name: 'googleworkspace::google.google_docs_default_view',
                flag: 'all-docs',
            )
            ->orderBy('created_at', 'desc');
    }

    public function fields(ResourceRequest $request): array
    {
        return [
            Text::make('name', __('googleworkspace::google.name'))
                ->creationRules('required')
                ->updateRules('filled'),
        ];
    }

    public function fieldsForIndex(): FieldsCollection
    {
        return new FieldsCollection([
            Text::make('name', __('googleworkspace::google.name'))->disableInlineEdit(),
            Text::make('is_available', __('googleworkspace::google.actions'))->disableInlineEdit(),
            CreatedAt::make()->hidden(),
            UpdatedAt::make()->hidden(),
        ]);
    }

    /**
     * Register permissions for the resource
     */
    public function registerPermissions(): void
    {
        $this->registerCommonPermissions();
    }
}
