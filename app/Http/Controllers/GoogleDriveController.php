<?php

namespace App\Http\Controllers;

use Google\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;

class GoogleDriveController extends Controller
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setClientId(env('GOOGLE_DRIVE_CLIENT_ID'));
        $this->client->setClientSecret(env('GOOGLE_DRIVE_CLIENT_SECRET'));
        $this->client->setRedirectUri(route('google.drive.callback'));
        $this->client->addScope(\Google_Service_Drive::DRIVE);
        $this->client->setAccessType('offline'); // Important for refresh token
        $this->client->setPrompt('consent'); // Important for refresh token
    }

    public function authorizeGoogleDrive()
    {
        return Redirect::to($this->client->createAuthUrl());
    }

    public function handleGoogleDriveCallback(Request $request)
    {
        if ($request->has('code')) {
            $this->client->fetchAccessTokenWithAuthCode($request->input('code'));
            $accessToken = $this->client->getAccessToken();

            // Store the refresh token
            $refreshToken = $accessToken['refresh_token'] ?? null;

            if ($refreshToken) {
                // Update .env file with the refresh token
                $envFile = app()->environmentFilePath();
                $contents = file_get_contents($envFile);
                $contents = preg_replace('/^GOOGLE_DRIVE_REFRESH_TOKEN=.*$/m', 'GOOGLE_DRIVE_REFRESH_TOKEN="' . $refreshToken . '"', $contents);
                file_put_contents($envFile, $contents);

                // Clear config cache to load new .env value
                
                return 'Google Drive authorized successfully! Refresh token saved.';
            } else {
                return 'Failed to get refresh token. Please ensure you granted offline access.';
            }
        } else {
            return 'Authorization failed: ' . $request->input('error', 'Unknown error');
        }
    }
}
