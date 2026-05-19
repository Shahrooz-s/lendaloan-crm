<?php

namespace Modules\GoogleWorkspace\Services\GoogleWorkspace;

use Google\Service\Drive;
use Google\Service\Slides;
use Modules\GoogleWorkspace\Models\GoogleToken;
use Modules\GoogleWorkspace\Models\GoogleSlides;
use Modules\GoogleWorkspace\Services\ModuleInit;
use Modules\GoogleWorkspace\Services\ModuleInitializationService;
use Modules\GoogleWorkspace\Services\GoogleWorkspace\GoogleClientFactory;

class GoogleSlidesService {
    private $client;

    public function __construct()
    {
        $this->client = GoogleClientFactory::make([
            Slides::PRESENTATIONS,
            Drive::DRIVE
        ]);

        $token = $this->getAccessToken();
        if ($token) {
            $this->client->setAccessToken($token);
        }
    }

    public function getAccessToken()
    {
        $user = auth()->user();

        $tokenData = GoogleToken::where('user_id', $user->id)->first();
        if (!$tokenData) return null;

        $token = json_decode($tokenData->access_token, true);

        if ($this->client->isAccessTokenExpired()) {
            $this->client->refreshToken($token['refresh_token']);
            $newToken = $this->client->getAccessToken();
            $tokenData->update(['access_token' => json_encode($newToken)]);
            return $newToken;
        }
        return $token;
    }

    public function syncSlides()
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
            'q' => "mimeType='application/vnd.google-apps.presentation'",
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
            GoogleSlides::upsert(
                $chunk->toArray(),
                ['drive_id'],
                ['name', 'description', 'is_available']
            );
        });

        return ["message" => "Sync completed"];
    }

    /**
     * Create a new Google Slide with the given name and description, then sync to DB.
     * @param string $name
     * @param string|null $description
     * @return array
     */
    public function createSlide($name)
    {
        try {
            $client = GoogleClientFactory::make([
                Slides::PRESENTATIONS,
                Drive::DRIVE
            ]);
            $token = $this->getAccessToken();
            if ($token) $client->setAccessToken($token);
            $service = new Slides($client);
            $presentation = new Slides\Presentation([
                'title' => $name
            ]);
            $created = $service->presentations->create($presentation);

            // Sync to local DB
            $this->syncSlides();

            return [
                'success' => true,
                'file' => [
                    'id' => $created->presentationId,
                    'name' => $created->title,
                    'url' => 'https://docs.google.com/presentation/d/' . $created->presentationId . '/edit',
                    'type' => 'slide',
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
