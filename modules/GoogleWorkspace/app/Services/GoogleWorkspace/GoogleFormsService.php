<?php

namespace Modules\GoogleWorkspace\Services\GoogleWorkspace;

use Google\Service\Drive;
use Google\Service\Forms;
use Illuminate\Support\Facades\Log;
use Modules\GoogleWorkspace\Models\GoogleForms;
use Modules\GoogleWorkspace\Models\GoogleToken;
use Modules\GoogleWorkspace\Services\ModuleInit;
use Modules\GoogleWorkspace\Services\ModuleInitializationService;
use Modules\GoogleWorkspace\Services\GoogleWorkspace\GoogleClientFactory;

class GoogleFormsService
{
    private $client;
    public function __construct()
    {
        $this->client = GoogleClientFactory::make([
            Forms::FORMS_BODY,
            Drive::DRIVE
        ]);

        $token = $this->getAccessToken();
        if ($token) {
            $this->client->setAccessToken($token);
        }
    }

    public function getAccessToken()
    {
        $tokenData = GoogleToken::where('user_id', \Auth::id())->first();
        if (!$tokenData) {
            return null;
        }
        $token = json_decode($tokenData->access_token, true);
        if ($this->client->isAccessTokenExpired()) {
            $this->client->refreshToken($token['refresh_token']);
            $newToken = $this->client->getAccessToken();
            $tokenData->update(['access_token' => json_encode($newToken)]);
            return $newToken;
        }
        return $token;
    }

    public function syncForms()
    {
        $moduleInit = new ModuleInit();
        $moduleInit->handle('googleworkspace');

        if (!ModuleInitializationService::isModuleActive("googleworkspace"))
        {
            abort(403, "Module is inactive. Please activate it via settings/googleworkspace.");
        }

        if (!$this->client->getAccessToken()) {
            abort(400, __('googleworkspace::google.google_authentication_error'));
        }

        $service = new Drive($this->client);
        $results = $service->files->listFiles([
            'q' => "mimeType='application/vnd.google-apps.form'",
            'fields' => 'files(id, name, ownedByMe, shared, description)',
        ]);

        $files = collect($results->getFiles());
        Log::info("SYNCED FILES: ", ['files' => $files ]);
        $formattedData = $files->map(function ($file) {
            $availability = json_encode([ $file->getId() , ($file->getShared() ? 1 : 0)]);
            return [
                'drive_id' => $file->getId(),
                'name' => $file->getName(),
                'is_available' => $availability,
            ];
        });

        $formattedData->chunk(100)->each(function ($chunk) {
            GoogleForms::upsert(
                $chunk->toArray(),
                ['drive_id'],
                ['name', 'is_available']
            );
        });

        return ["message" => "Sync completed"];
    }

    /**
     * Create a new Google Form with the given name and description, then sync to DB.
     * @param string $name
     * @param string|null $description
     * @return array
     */
    public function createForm($name)
    {
        try {
            $client = GoogleClientFactory::make([
                Forms::FORMS_BODY,
                Drive::DRIVE
            ]);
            $token = $this->getAccessToken();
            if ($token) $client->setAccessToken($token);
            $service = new Forms($client);
            $form = new Forms\Form([
                'info' => new Forms\Info(['title' => $name]),
            ]);
            $created = $service->forms->create($form);

            // Sync to local DB
            $this->syncForms();

            return [
                'success' => true,
                'file' => [
                    'id' => $created->formId,
                    'name' => $created->info['title'] ?? $name,
                    'url' => 'https://docs.google.com/forms/d/' . $created->formId . '/edit',
                    'type' => 'form',
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
