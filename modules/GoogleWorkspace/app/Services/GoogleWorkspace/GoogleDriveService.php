<?php

namespace Modules\GoogleWorkspace\Services\GoogleWorkspace;

use Google\Client;
use Google\Service\Drive;
use Modules\GoogleWorkspace\Models\GoogleToken;
use Illuminate\Support\Facades\Auth;
use Modules\GoogleWorkspace\Models\GoogleDrives;
use Modules\GoogleWorkspace\Services\ModuleInit;
use Modules\GoogleWorkspace\Services\ModuleInitializationService;
use Modules\GoogleWorkspace\Services\GoogleWorkspace\GoogleClientFactory;

class GoogleDriveService
{
    protected $client;

    public function __construct()
    {
        $this->client = GoogleClientFactory::make([
            Drive::DRIVE
        ]);

        $token = $this->getAccessToken();
        if ($token) {
            $this->client->setAccessToken($token);
        }
    }

    public function getAccessToken()
    {
        $tokenData = GoogleToken::where('user_id', Auth::id())->first();
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

    public function listFiles()
    {
        if (!$this->client->getAccessToken()) {
            abort(400, __('googleworkspace::google.google_authentication_error'));
        }

        $service = new Drive($this->client);
        $results = $service->files->listFiles([
            'fields' => 'files(id, name, mimeType, webViewLink, ownedByMe, shared)',
        ]);

        return $results->getFiles();
    }

    public function listFileByType($type)
    {
        $mimeTypes = [
            'docs' => "mimeType='application/vnd.google-apps.document'",
            'sheets' => "mimeType='application/vnd.google-apps.spreadsheet'",
            'slides' => "mimeType='application/vnd.google-apps.presentation'",
            'forms' => "mimeType='application/vnd.google-apps.form'",
        ];

        $query = $mimeTypes[$type] ?? null;

        if (!$query) {
            return response()->json([], 400);
        }

        if (!$this->client->getAccessToken()) {
            return ['error' => __('googleworkspace::google.google_authentication_error')];
        }

        $drive = new Drive($this->client);

        $response = $drive->files->listFiles([
            'q' => $query,
            'fields' => 'files(id, name, modifiedTime, webViewLink, ownedByMe)',
        ]);

        return $response->getFiles();
    }

    public function getDocsByProxy($type, $documentId)
    {
        $user = auth()->user();
        $googleToken = GoogleToken::where('user_id', $user->id)->first();

        if (!$googleToken) {
            return response()->json(['error' => 'No token found.'], 401);
        }

        $token = json_decode($googleToken->access_token, true);

        $client = new Client();
        $client->setClientId(settings()->get('google_client_id'));
        $client->setClientSecret(settings()->get('google_client_secret'));
        $client->setAccessToken($token);

        // Refresh if expired
        if ($client->isAccessTokenExpired() && isset($googleToken->refresh_token)) {
            $client->fetchAccessTokenWithRefreshToken($googleToken->refresh_token);
            $newToken = $client->getAccessToken();
            $googleToken->update(['access_token' => json_encode($newToken)]);
            $client->setAccessToken($newToken);
        }

        // Determine URL based on document type
        switch ($type) {
            case 'docs':
                $iframeUrl = "https://docs.google.com/document/d/{$documentId}/edit?usp=drivesdk";
                break;
            case 'sheets':
                $iframeUrl = "https://docs.google.com/spreadsheets/d/{$documentId}/edit?usp=drivesdk";
                break;
            case 'slides':
                $iframeUrl = "https://docs.google.com/presentation/d/{$documentId}/edit?usp=drivesdk";
                break;
            case 'forms':
                $iframeUrl = "https://docs.google.com/forms/d/{$documentId}/edit?usp=drivesdk";
                break;
            case 'drives':
                $iframeUrl = "https://docs.google.com/file/d/{$documentId}/edit?usp=drivesdk";
                break;
            default:
                $iframeUrl = "https://drive.google.com/file/d/{$documentId}/edit?usp=drivesdk";
        }

        return $iframeUrl;
    }


    public function deleteDoc($type, $documentId)
    {
        $moduleInit = new ModuleInit();
        $moduleInit->handle('googleworkspace');

        if (!ModuleInitializationService::isModuleActive("googleworkspace"))
        {
            abort(403, "Module is inactive. Please activate it via settings/googleworkspace.");
        }

        $user = auth()->user();
        $tokenModel = GoogleToken::where('user_id', $user->id)->first();

        if (!$tokenModel) {
            return response()->json(['error' => 'No Google token found.'], 401);
        }

        $accessToken = json_decode($tokenModel->access_token, true);

        $client = new Client();
        $client->setAccessToken($accessToken);

        if ($client->isAccessTokenExpired()) {
            if (!$tokenModel->refresh_token) {
                return response()->json(['error' => 'Refresh token missing.'], 401);
            }

            $newToken = $client->fetchAccessTokenWithRefreshToken($tokenModel->refresh_token);
            $client->setAccessToken($newToken);

            $tokenModel->access_token = json_encode($newToken);
            $tokenModel->save();
        }

        $drive = new Drive($client);

        $model = match ($type) {
            'docs'   => \Modules\GoogleWorkspace\Models\GoogleDocs::class,
            'sheets' => \Modules\GoogleWorkspace\Models\GoogleSheets::class,
            'slides' => \Modules\GoogleWorkspace\Models\GoogleSlides::class,
            'forms'  => \Modules\GoogleWorkspace\Models\GoogleForms::class,
            'drives'  => \Modules\GoogleWorkspace\Models\GoogleDrives::class,
            default  => null
        };

        if (!$model) {
            return response()->json(['error' => 'Unsupported document type.'], 400);
        }

        try {
            $drive->files->delete($documentId);
        } catch (\Google\Service\Exception $e) {
            if ($e->getCode() == 404) {
                $model::where('drive_id', $documentId)->delete();
                return response()->json(['message' => 'File not found in Drive, deleted locally.']);
            }

            return response()->json(['error' => 'Google Drive error: ' . $e->getMessage()], 500);
        }

        $model::where('drive_id', $documentId)->delete();

        return true;
    }

    public function syncAllFiles()
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
            'fields' => 'files(id, name, description, mimeType, ownedByMe, shared)',
        ]);

        $files = collect($results->getFiles());

        $formattedData = $files->map(function ($file) {
            $availability = json_encode([ $file->getId() , ( $file->getShared() ? 1 : 0)]);
            return [
                'drive_id' => $file->getId(),
                'name' => $file->getName(),
                'mime_type' => $file->getMimeType(),
                'description' => $file->getDescription(),
                'is_available' => $availability,
            ];
        });

        $formattedData->chunk(100)->each(function ($chunk) {
            GoogleDrives::upsert(
                $chunk->toArray(),
                ['drive_id'],
                ['name', 'mime_type', 'description', 'is_available']
            );
        });

        return ["message" => "Sync completed"];
    }

    /**
     * Create a new Google Drive file (blank) with the given name and description, then sync to DB.
     * @param string $name
     * @param string|null $description
     * @param string|null $mimeType
     * @return array
     */
    public function createDriveFile($name, $description = null, $mimeType = 'application/vnd.google-apps.file')
    {
        try {
            $client = GoogleClientFactory::make([
                Drive::DRIVE
            ]);
            $token = $this->getAccessToken();
            if ($token) $client->setAccessToken($token);
            $service = new Drive($client);
            $fileMetadata = [
                'name' => $name,
                'description' => $description,
                'mimeType' => $mimeType
            ];
            $created = $service->files->create(new Drive\DriveFile($fileMetadata), ['fields' => 'id, name, description, mimeType, webViewLink']);

            // Sync to local DB
            $this->syncAllFiles();

            return [
                'success' => true,
                'file' => [
                    'id' => $created->id,
                    'name' => $created->name,
                    'description' => $created->description,
                    'mime_type' => $created->mimeType,
                    'url' => $created->webViewLink,
                    'type' => 'drive',
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Upload a file to Google Drive and sync to DB.
     * @param \Illuminate\Http\UploadedFile $uploadedFile
     * @param string|null $name
     * @param string|null $description
     * @param string|null $mimeType
     * @return array
     */
    public function uploadDriveFile($uploadedFile, $name = null, $description = null, $mimeType = null)
    {
        try {
            $client = GoogleClientFactory::make([Drive::DRIVE]);
            $token = $this->getAccessToken();
            if ($token) $client->setAccessToken($token);
            $service = new Drive($client);

            $fileMetadata = [
                'name' => $name ?? $uploadedFile->getClientOriginalName(),
                'description' => $description,
            ];
            $mimeType = $mimeType ?? $uploadedFile->getMimeType();

            $created = $service->files->create(
                new Drive\DriveFile($fileMetadata),
                [
                    'data' => file_get_contents($uploadedFile->getRealPath()),
                    'mimeType' => $mimeType,
                    'uploadType' => 'multipart',
                    'fields' => 'id, name, description, mimeType, webViewLink'
                ]
            );

            // Sync to local DB
            $this->syncAllFiles();

            return [
                'success' => true,
                'file' => [
                    'id' => $created->id,
                    'name' => $created->name,
                    'description' => $created->description,
                    'mime_type' => $created->mimeType,
                    'url' => $created->webViewLink,
                    'type' => 'drive',
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
