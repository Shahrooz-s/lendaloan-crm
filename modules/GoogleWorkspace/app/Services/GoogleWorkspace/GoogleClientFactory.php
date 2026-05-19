<?php

namespace Modules\GoogleWorkspace\Services\GoogleWorkspace;

use Google\Client;
use Modules\GoogleWorkspace\Models\GoogleSetting;

class GoogleClientFactory
{
    public static function make(array $scopes = [], $setToken = true)
    {
        $clientId = settings()->get('google_client_id');
        $clientSecret = settings()->get('google_client_secret');
        $projectId = settings()->get('google_project_id');

        if (!$clientId || !$clientSecret || !$projectId) {
            abort(400, 'Google API credentials are missing. Please set client ID, client secret and project id google settings.');
        }
        $redirectUri = settings()->get('google_redirect_uri') ?? config('app.url') . '/api/google/callback';
        $appUrl = config('app.url');
        $frontendUrl = config('config.app.frontend_url');

        $credentials = [
            'web' => [
                'client_id' => $clientId,
                'project_id' => $projectId,
                'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                'token_uri' => 'https://oauth2.googleapis.com/token',
                'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
                'client_secret' => $clientSecret,
                'redirect_uris' => [$redirectUri],
                'javascript_origins' => [$frontendUrl ?: $appUrl],
            ]
        ];

        $client = new Client();
        $client->setAuthConfig($credentials);
        $client->setRedirectUri($redirectUri);
        foreach ($scopes as $scope) {
            $client->addScope($scope);
        }
        $client->setPrompt('consent');
        $client->setAccessType('offline');
        return $client;
    }
}
