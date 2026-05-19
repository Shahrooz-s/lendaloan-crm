<?php

namespace Modules\GoogleWorkspace\Actions;

use Modules\Core\Actions\Action;
use Modules\Core\Actions\ActionFields;
use Modules\Core\Fields\Text;
use Modules\Core\Http\Requests\ResourceRequest;
use Modules\GoogleWorkspace\Services\GoogleWorkspace\GoogleDriveService;
use Illuminate\Support\Collection;

class CreateGoogleDriveFileAction extends Action
{
    public ?string $name = 'create_google_drive_file';
    public string $label = 'Create Google Drive File';
    public string $icon = 'Plus';

    public function handle(Collection $models, ActionFields $fields)
    {
        [$name, $description, $mime_type] = $fields->all();
        $service = new GoogleDriveService();
        $result = $service->createDriveFile($name, $description, $mime_type);
        if ($result['success']) {
            return $this->success(__('googleworkspace::google.drive_file_created_successfully'));
        }
        return $this->error($result['message'] ?? __('googleworkspace::google.failed_to_create_drive_file'));
    }

    public function fields(ResourceRequest $request): array
    {
        return [
            Text::make('name', __('googleworkspace::google.name'))->rules('required'),
            Text::make('description', __('googleworkspace::google.description'))->rules('nullable'),
            Text::make('mime_type', __('googleworkspace::google.mime_type'))->rules('nullable'),
        ];
    }
}
