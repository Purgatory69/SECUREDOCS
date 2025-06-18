<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Services\Webauthn as WebauthnService;
use LaravelWebauthn\Facades\Webauthn;
use Illuminate\Validation\ValidationException;

class WebAuthnController extends Controller
{
    protected $webauthn;

    /**
     * Create a new controller instance.
     */
    public function __construct(WebauthnService $webauthn)
    {
        $this->webauthn = $webauthn;
        $this->middleware('auth', ['except' => ['loginOptions', 'loginVerify']]);
    }

    /**
     * Show the WebAuthn management page.
     */
    public function index()
    {
        $webauthnKeys = Auth::user()->webauthnKeys;
        
        return view('webauthn.manage', [
            'webauthnKeys' => $webauthnKeys,
        ]);
    }

    /**
     * Delete a WebAuthn key.
     */
    public function destroy($id)
    {
        $key = WebauthnKey::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $key->delete();

        return redirect()->route('webauthn.index')
            ->with('status', 'Device removed successfully.');
    }
    
    /**
     * Register a new WebAuthn device.
     */
    public function register(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
        ]);
        
        return view('webauthn.register', [
            'name' => $request->input('name'),
        ]);
    }
    
    /**
     * Get registration options for WebAuthn.
     */
    public function registerOptions(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
        ]);
        
        try {
            $options = Webauthn::prepareAttestation(Auth::user());
            return response()->json($options);
        } catch (\Exception $e) {
            Log::error('WebAuthn registration options error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create registration options'], 500);
        }
    }
    
    /**
     * Verify the registration response from the authenticator.
     */
    public function registerVerify(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'data' => 'required',
        ]);
        
        try {
            $registered = Webauthn::register(       
                Auth::user(),
                $request->input('data'),
                $request->input('name')
            );
            
            if ($registered) {
                return response()->json([
                    'success' => true,
                    'message' => 'Device registered successfully',
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Device registration failed',
            ], 400);
        } catch (\Exception $e) {
            Log::error('WebAuthn registration verification error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Device registration failed: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get login options for WebAuthn.
     */
    public function loginOptions(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email']);
            $email = $request->input('email');

            $user = User::where('email', $email)->first();

            if (! $user) {
                return response()->json(['error' => 'User not found.'], 404);
            }

            $options = Webauthn::prepareAssertion($user);
            return response()->json($options);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'messages' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('WebAuthn login options error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create login options: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Verify the login response from the authenticator.
     */
    public function loginVerify(Request $request)
    {
        $this->validate($request, [
            'data' => 'required',
        ]);
        
        try {
            $user = Webauthn::login($request->input('data'));
            
            if ($user) {
                Auth::login($user);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'redirect' => route('dashboard'),
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
            ], 401);
        } catch (\Exception $e) {
            Log::error('WebAuthn login verification error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Login failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
