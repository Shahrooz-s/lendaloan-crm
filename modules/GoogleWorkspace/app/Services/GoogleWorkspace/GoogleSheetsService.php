<?php

namespace Modules\GoogleWorkspace\Services\GoogleWorkspace;

use Google\Service\Drive;
use Modules\GoogleWorkspace\Models\GoogleToken;
use Modules\GoogleWorkspace\Models\GoogleSheets;
use Modules\GoogleWorkspace\Services\ModuleInit;
use Modules\GoogleWorkspace\Services\ModuleInitializationService;
use Modules\GoogleWorkspace\Services\GoogleWorkspace\GoogleClientFactory;

class GoogleSheetsService {
    private $client;

    public function __construct()
    {
        $this->client = GoogleClientFactory::make([
            \Google_Service_Sheets::SPREADSHEETS,
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

    public function syncSheets()
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
            'q' => "mimeType='application/vnd.google-apps.spreadsheet'",
            'fields' => 'files(id, name, description, ownedByMe, shared)',
        ]);

        $files = collect($results->getFiles());

        $formattedData = $files->map(function ($file) {
            $availability = json_encode([ $file->getId() , ($file->getShared() ? 1 : 0)]);
            return [
                'drive_id' => $file->getId(),
                'name' => $file->getName(),
                'description' => $file->getDescription() ?? '',
                'is_available' => $availability,
            ];
        });

        $formattedData->chunk(100)->each(function ($chunk) {
            GoogleSheets::upsert(
                $chunk->toArray(),
                ['drive_id'],
                ['name', 'description', 'is_available']
            );
        });

        return ["message" => "Sync completed"];
    }

    /**
     * Create a new Google Sheet with the given name and description, then sync to DB.
     * @param string $name
     * @param string|null $description
     * @return array
     */
    public function createSheet($name)
    {
        try {
            $client = GoogleClientFactory::make([
                \Google_Service_Sheets::SPREADSHEETS,
                \Google_Service_Drive::DRIVE
            ]);
            $token = $this->getAccessToken();
            if ($token) $client->setAccessToken($token);
            $service = new \Google_Service_Sheets($client);
            $spreadsheet = new \Google_Service_Sheets_Spreadsheet([
                'properties' => ['title' => $name]
            ]);
            $created = $service->spreadsheets->create($spreadsheet);

            // Sync to local DB
            $this->syncSheets();

            return [
                'success' => true,
                'file' => [
                    'id' => $created->spreadsheetId,
                    'name' => $created->properties->title,
                    'url' => $created->spreadsheetUrl,
                    'type' => 'sheet',
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
