<?php

namespace Modules\GoogleWorkspace\Actions;

use Modules\Core\Actions\Action;
use Modules\GoogleWorkspace\Services\GoogleWorkspace\GoogleDocsService;
use Modules\Core\Actions\ActionFields;
use Illuminate\Support\Collection;
use Modules\Core\Http\Requests\ResourceRequest;
use Modules\Core\Facades\Innoclapps;
use Modules\GoogleWorkspace\Models\GoogleDocs;
use Modules\Core\Fields\Text;


class CreateGoogleDocAction extends Action
{
    public ?string $name = 'create_google_doc';
    public string $label = 'Create Google Doc';
    public string $icon = 'Plus';

    public function handle(Collection $models, ActionFields $fields)
    {
        [$name, $description] = $fields->all();

        $service = new GoogleDocsService();
        $result = $service->createDoc($name, $description);
        if ($result['success']) {
            return $this->success(__('googleworkspace::google.doc_created_successfully'));
        }
        return $this->error($result['message'] ?? __('googleworkspace::google.failed_to_create_doc'));
    }

    public function fields(ResourceRequest $request): array
    {
        return [
            Text::make('name', __('googleworkspace::google.name'))->required(),
            Text::make('description', __('googleworkspace::google.description'))->required(),
        ];
    }
    /**
     * Get the confirmation button text.
     */
    public function confirmButtonText(): string
    {
        return __('core::app.create');
    }

    /**
     * Action name.
     */
    public function name(): string
    {
        return __('googleworkspace::google.create');
    }
}
