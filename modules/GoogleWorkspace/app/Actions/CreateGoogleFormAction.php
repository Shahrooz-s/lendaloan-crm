<?php

namespace Modules\GoogleWorkspace\Actions;

use Modules\Core\Actions\Action;
use Modules\Core\Fields\Text;
use Modules\Core\Http\Requests\ResourceRequest;
use Modules\GoogleWorkspace\Services\GoogleWorkspace\GoogleFormsService;
use Illuminate\Support\Collection;

class CreateGoogleFormAction extends Action
{
    public ?string $name = 'create_google_form';
    public string $label = 'Create Google Form';
    public string $icon = 'Plus';

    public function handle(Collection $models, \Modules\Core\Actions\ActionFields $fields)
    {
        [$name, $description] = $fields->all();
        $service = new GoogleFormsService();
        $result = $service->createForm($name, $description);
        if ($result['success']) {
            return $this->success(__('googleworkspace::google.form_created_successfully'));
        }
        return $this->error($result['message'] ?? __('googleworkspace::google.failed_to_create_form'));
    }

    public function fields(ResourceRequest $request): array
    {
        return [
            Text::make('name', __('googleworkspace::google.name'))->rules('required'),
            Text::make('description', __('googleworkspace::google.description'))->rules('nullable'),
        ];
    }
}
