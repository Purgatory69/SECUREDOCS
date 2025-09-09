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
        $user = Auth::user();
        $credentials = $user ? $user->webAuthnCredentials : collect();
        return view('webauthn.manage', compact('credentials'));
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