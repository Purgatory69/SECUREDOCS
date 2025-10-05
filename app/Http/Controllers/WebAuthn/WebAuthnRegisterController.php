<?php

namespace App\Http\Controllers\WebAuthn;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Laragear\WebAuthn\Http\Requests\AttestationRequest;
use Laragear\WebAuthn\Http\Requests\AttestedRequest;

class WebAuthnRegisterController extends \App\Http\Controllers\Controller
{
    public function options(AttestationRequest $request)
    {
        // Debug logging for WebAuthn registration options
        Log::debug('WebAuthn Registration Options Request', [
            'request_host' => $request->getHost(),
            'request_url' => $request->url(),
            'request_scheme' => $request->getScheme(),
            'user_agent' => $request->userAgent(),
            'webauthn_config' => [
                'rp_id' => config('webauthn.relying_party.id'),
                'rp_name' => config('webauthn.relying_party.name'),
                'origins' => config('webauthn.origins'),
                'app_url' => config('app.url'),
            ]
        ]);

        try {
            $options = $request
                ->fastRegistration()
                ->toCreate();
                
            Log::debug('WebAuthn Registration Options Generated', [
                'options_type' => get_class($options)
            ]);
            
            return $options;
        } catch (\Exception $e) {
            Log::error('WebAuthn Registration Options Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating registration options',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function register(AttestedRequest $request): JsonResponse
    {
        $user = $request->user() ?? User::findOrFail($request->input('user_id'));
        $credential = $request->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Device registered successfully',
            'credential' => $credential
        ]);
    }
}