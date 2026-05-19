<?php

namespace Modules\GoogleWorkspace\Actions;

use Modules\Core\Actions\Action;
use Modules\Core\Actions\ActionFields;
use Modules\Core\Fields\Text;
use Modules\Core\Http\Requests\ResourceRequest;
use Illuminate\Support\Collection;
use Modules\GoogleWorkspace\Services\GoogleWorkspace\GoogleSheetsService;

class CreateGoogleSheetAction extends Action
{
    public ?string $name = 'create_google_sheet';
    public string $label = 'Create Google Sheet';
    public string $icon = 'Plus';

    public function handle(Collection $models, ActionFields $fields)
    {
        [$name, $description] = $fields->all();
        $service = new GoogleSheetsService();
        $result = $service->createSheet($name, $description);
        if ($result['success']) {
            return $this->success(__('googleworkspace::google.sheet_created_successfully'));
        }
        return $this->error($result['message'] ?? __('googleworkspace::google.failed_to_create_sheet'));
    }

    public function fields(ResourceRequest $request): array
    {
        return [
            Text::make('name', __('googleworkspace::google.name'))->rules('required'),
            Text::make('description', __('googleworkspace::google.description'))->rules('nullable'),
        ];
    }
}
