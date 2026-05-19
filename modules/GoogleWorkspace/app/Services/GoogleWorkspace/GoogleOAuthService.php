<?php

namespace Modules\GoogleWorkspace\Services\GoogleWorkspace;

use Google\Service\Drive;
use Google\Service\Docs;
use Google\Service\Forms;
use Google\Service\Slides;
use Modules\GoogleWorkspace\Models\GoogleToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Modules\GoogleWorkspace\Services\ModuleInit;
use Modules\GoogleWorkspace\Services\ModuleInitializationService;
use Modules\GoogleWorkspace\Services\GoogleWorkspace\GoogleClientFactory;

class GoogleOAuthService
{
    protected $client;

    public function __construct()
    {
        $this->client = GoogleClientFactory::make([
            Docs::DOCUMENTS,
            Slides::PRESENTATIONS,
            Forms::FORMS_BODY,
            Drive::DRIVE
        ]);
    }

    public function getAuthUrl()
    {
        $moduleInit = new ModuleInit();
        $moduleInit->handle('googleworkspace');

        if (!ModuleInitializationService::isModuleActive("googleworkspace"))
        {
            abort(403, "Module is inactive. Please activate it via settings/googleworkspace.");
        }

        $state = Crypt::encrypt([
            'user_id' => Auth::id(),
            'redirect_url' => config('config.app.frontend_url') . '/google/success',
        ]);

        $this->client->setState($state);

        return $this->client->createAuthUrl();
    }

    public function handleCallback($request)
    {
        $code = $request->get('code');
        $userDetail = Crypt::decrypt($request->get('state'));

        $this->client->authenticate($code);
        $token = $this->client->getAccessToken();
        $refreshToken = $this->client->getRefreshToken();

        GoogleToken::updateOrCreate(
            ['user_id' => $userDetail['user_id']],
            [
                'access_token' => json_encode($token),
                'refresh_token' => $refreshToken ?? null,
            ]
        );

        return $userDetail['redirect_url'] ?? '/';
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

    public function getDocs()
    {
        $token = $this->getAccessToken();
        if (!$token) return [];

        $this->client->setAccessToken($token);
        $service = new Drive($this->client);

        $results = $service->files->listFiles([
            'q' => "mimeType='application/vnd.google-apps.document'",
            'fields' => 'files(id, name, webViewLink)',
        ]);

        return $results->getFiles();
    }
}
