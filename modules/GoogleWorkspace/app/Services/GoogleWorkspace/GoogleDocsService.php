<?php

namespace Modules\GoogleWorkspace\Services\GoogleWorkspace;


use Google\Service\Docs;
use Google\Service\Drive;
use Modules\GoogleWorkspace\Models\GoogleToken;
use Modules\GoogleWorkspace\Models\GoogleDocs;
use Modules\GoogleWorkspace\Services\ModuleInit;
use Modules\GoogleWorkspace\Services\ModuleInitializationService;
use Modules\GoogleWorkspace\Services\GoogleWorkspace\GoogleClientFactory;

class GoogleDocsService
{
    protected $client;

    public function __construct()
    {
        $this->client = GoogleClientFactory::make([
            Docs::DOCUMENTS,
            Drive::DRIVE
        ]);

        $token = $this->getAccessToken();
        if ($token) {
            $this->client->setAccessToken($token);
        }
    }

    public function getAccessToken()
    {
        $tokenData = GoogleToken::where('user_id', auth()->id())->first();
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

    public function listDocs()
    {
        if (!$this->client->getAccessToken()) {
            abort(400, __('googleworkspace::google.google_authentication_error'));
        }

        $service = new Drive($this->client);
        $results = $service->files->listFiles([
            'q' => "mimeType='application/vnd.google-apps.document'",
            'fields' => 'files(id, name, webViewLink)',
        ]);

        return $results->getFiles();
    }

    public function syncDocs()
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
            'q' => "mimeType='application/vnd.google-apps.document'",
            'fields' => 'files(id, name, description, ownedByMe, shared)',
        ]);

        $files = collect($results->getFiles());

        $formattedData = $files->map(function ($file) {
            $availability = json_encode([ $file->getId() , ($file->getShared() ? 1 : 0)]);
            return [
                'drive_id' => $file->getId(),
                'name' => $file->getName(),
                'description' => $file->getDescription(),
                'is_available' => $availability,
            ];
        });

        $formattedData->chunk(100)->each(function ($chunk) {
            GoogleDocs::upsert(
                $chunk->toArray(),
                ['drive_id'],
                ['name', 'description', 'is_available']
            );
        });

        return ["message" => "Sync completed"];
    }

    public function getDocContent($docId)
    {
        if (!$this->client->getAccessToken()) {
            return ['error' => __('googleworkspace::google.google_authentication_error')];
        }

        $service = new Docs($this->client);
        $document = $service->documents->get($docId);

        return $document->getBody()->getContent();
    }

    /**
     * Create a new Google Doc with the given name and description, then sync to DB.
     * @param string $name
     * @param string|null $description
     * @return array
     */
    public function createDoc($name)
    {
        try {
            $client = GoogleClientFactory::make([
                Docs::DOCUMENTS,
                Drive::DRIVE
            ]);
            $token = $this->getAccessToken();

            if ($token) $client->setAccessToken($token);
            $service = new Docs($client);
            $doc = new Docs\Document([
                'title' => $name
            ]);
            $created = $service->documents->create($doc);

            // Sync to local DB
            $this->syncDocs();

            return [
                'success' => true,
                'file' => [
                    'id' => $created->documentId,
                    'name' => $created->title,
                    'url' => 'https://docs.google.com/document/d/' . $created->documentId . '/edit',
                    'type' => 'doc',
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
