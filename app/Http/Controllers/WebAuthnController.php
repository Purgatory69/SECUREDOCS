<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laragear\WebAuthn\Http\Requests\AssertionRequest;
use Laragear\WebAuthn\Http\Requests\AssertedRequest;
use Laragear\WebAuthn\Http\Requests\AttestationRequest;
use Laragear\WebAuthn\Http\Requests\AttestedRequest;
use Laragear\WebAuthn\Models\WebAuthnCredential;

class WebAuthnController extends Controller
{
    /**
     * Display the WebAuthn credentials management page.
     */
    public function index()
    {
        try {
            $user = Auth::user();
            
            // Check if user has webAuthnCredentials relationship
            if (!$user || !method_exists($user, 'webAuthnCredentials')) {
                $credentials = collect();
            } else {
                $credentials = $user->webAuthnCredentials->map(function ($credential) {
                    return $this->enhanceCredentialWithType($credential);
                });
            }
            
            return view('webauthn.manage', compact('credentials'));
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('WebAuthn index error: ' . $e->getMessage());
            
            // Return view with empty credentials
            $credentials = collect();
            return view('webauthn.manage', compact('credentials'));
        }
    }

    /**
     * Enhance credential with authenticator type detection
     */
    private function enhanceCredentialWithType($credential)
    {
        $aaguid = $credential->aaguid ?? '';
        $attachment = $credential->attachment ?? 'unknown';
        
        // Windows Hello AAGUIDs
        $windowsHelloAAGUIDs = [
            '6028b017-b1d4-4c02-b4b3-afcdafc96bb2', // Windows Hello Face
            '08987058-cadc-4b81-b6e1-30de50dcbe96', // Windows Hello Fingerprint
            'dd4ec289-e01d-41c9-bb89-70fa845d4bf2', // Windows Hello PIN
        ];
        
        // Apple AAGUIDs
        $appleAAGUIDs = [
            'adce0002-35bc-4468-8a6b-692e3cb44c3c', // Touch ID
            '9d3deeb3-4064-4b5a-8e9a-4e9a8e9a8e9a', // Face ID
        ];
        
        // Determine authenticator type
        $type = 'security-key';
        $icon = '🔑';
        $displayName = 'Security Key';
        
        if (in_array($aaguid, $windowsHelloAAGUIDs)) {
            if (strpos($aaguid, '6028b017') === 0) {
                $type = 'face';
                $icon = '👤';
                $displayName = 'Windows Hello Face';
            } elseif (strpos($aaguid, '08987058') === 0) {
                $type = 'fingerprint';
                $icon = '👆';
                $displayName = 'Windows Hello Fingerprint';
            } else {
                $type = 'biometric';
                $icon = '🔒';
                $displayName = 'Windows Hello';
            }
        } elseif (in_array($aaguid, $appleAAGUIDs)) {
            if (strpos($aaguid, 'adce0002') === 0) {
                $type = 'fingerprint';
                $icon = '👆';
                $displayName = 'Touch ID';
            } else {
                $type = 'face';
                $icon = '👤';
                $displayName = 'Face ID';
            }
        } elseif ($attachment === 'platform') {
            $type = 'biometric';
            $icon = '🔒';
            $displayName = 'Platform Authenticator';
        }
        
        $credential->authenticator_type = $type;
        $credential->authenticator_icon = $icon;
        $credential->authenticator_display_name = $displayName;
        $credential->attachment_type = $attachment;
        
        return $credential;
    }

    /**
     * Delete a WebAuthn credential.
     */
    public function destroy($id)
    {
        $credential = WebAuthnCredential::findOrFail($id);

        if ($credential->authenticatable_id !== Auth::id()) {
            abort(403);
        }

        $credential->delete();

        return back()->with('status', 'Security key removed successfully.');
    }

    /**
     * Generate the options for logging in with a security key.
     */
    public function loginOptions(AssertionRequest $request)
    {
        return $request->toVerify($request->validate(['email' => 'sometimes|email|string']));
    }

    /**
     * Verify the login assertion and log the user in.
     */
    public function loginVerify(AssertedRequest $request)
    {
        if ($user = $request->login()) {
            Auth::login($user);
            return response()->json(['verified' => true, 'redirect' => url('/redirect-after-login')]);
        }

        return response()->json(['verified' => false, 'message' => 'Authentication failed'], 401);
    }

    /**
     * Generate the optplaraions for registering a new security key.
     */
    public function registerOptions(AttestationRequest $request)
    {
        return $request->toCreate();
    }

    /**
     * Save the new security key to the database.
     */
    public function registerVerify(AttestedRequest $request)
    {
        try {
            $credential = $request->save();

            return response()->json([
                'success' => true,
                'message' => 'Security key registered successfully',
                'credential' => $credential
            ]);
        } catch (\Exception $e) {
            Log::error('WebAuthn registration error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 422);
        }
    }
}