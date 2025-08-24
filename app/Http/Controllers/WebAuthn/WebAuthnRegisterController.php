<?php

namespace App\Http\Controllers\WebAuthn;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Laragear\WebAuthn\Http\Requests\AttestationRequest;
use Laragear\WebAuthn\Http\Requests\AttestedRequest;

class WebAuthnRegisterController extends \App\Http\Controllers\Controller
{
    public function options(AttestationRequest $request)
    {
        return $request
            ->fastRegistration()
            ->toCreate();
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