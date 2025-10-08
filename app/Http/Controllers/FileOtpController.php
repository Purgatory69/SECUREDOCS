<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\PermanentStorage;
use App\Models\FileOtpSecurity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class FileOtpController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Enable OTP protection for a file
     */
    public function enableOtp(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file_type' => 'required|in:regular,permanent',
                'file_id' => 'required|integer',
                'require_otp_for_download' => 'boolean',
                'require_otp_for_preview' => 'boolean',
                'otp_valid_duration_minutes' => 'integer|min:5|max:60'
            ]);

            $user = Auth::user();
            
            Log::info('OTP Enable Request', [
                'user_id' => $user->id,
                'file_type' => $request->file_type,
                'file_id' => $request->file_id,
                'request_data' => $request->all()
            ]);
            
            // Verify file ownership
            if ($request->file_type === 'regular') {
                $file = File::where('id', $request->file_id)
                    ->where('user_id', $user->id)
                    ->first();
                
                if (!$file) {
                    Log::error('File not found for OTP enable', [
                        'user_id' => $user->id,
                        'file_id' => $request->file_id
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'File not found or access denied'
                    ], 404);
                }
                
                // Mark file as confidential - use raw SQL to handle PostgreSQL boolean casting
                DB::table('files')
                    ->where('id', $file->id)
                    ->update([
                        'is_confidential' => DB::raw('true'),
                        'confidential_enabled_at' => now(),
                        'updated_at' => now()
                    ]);
                
                $fileId = $file->id;
                $permanentStorageId = null;
            } else {
                $permanentFile = PermanentStorage::where('id', $request->file_id)
                    ->where('user_id', $user->id)
                    ->firstOrFail();
                
                $fileId = null;
                $permanentStorageId = $permanentFile->id;
            }

            // Create or update OTP security record
            Log::info('Creating OTP security record', [
                'user_id' => $user->id,
                'file_id' => $fileId,
                'permanent_storage_id' => $permanentStorageId,
                'settings' => [
                    'require_otp_for_download' => $request->get('require_otp_for_download', true),
                    'require_otp_for_preview' => $request->get('require_otp_for_preview', false),
                    'otp_valid_duration_minutes' => $request->get('otp_valid_duration_minutes', 10),
                ]
            ]);
            
            $otpSecurity = FileOtpSecurity::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'file_id' => $fileId,
                    'permanent_storage_id' => $permanentStorageId,
                ],
                [
                    'is_otp_enabled' => DB::raw('true'),
                    'require_otp_for_download' => $request->get('require_otp_for_download', true) ? DB::raw('true') : DB::raw('false'),
                    'require_otp_for_preview' => $request->get('require_otp_for_preview', false) ? DB::raw('true') : DB::raw('false'),
                    'otp_valid_duration_minutes' => $request->get('otp_valid_duration_minutes', 10),
                    'max_otp_attempts' => 3
                ]
            );
            
            Log::info('OTP security record created/updated', [
                'otp_security_id' => $otpSecurity->id,
                'is_otp_enabled' => $otpSecurity->is_otp_enabled
            ]);

            return response()->json([
                'success' => true,
                'message' => 'OTP protection enabled successfully',
                'otp_security' => [
                    'id' => $otpSecurity->id,
                    'is_otp_enabled' => $otpSecurity->is_otp_enabled,
                    'require_otp_for_download' => $otpSecurity->require_otp_for_download,
                    'require_otp_for_preview' => $otpSecurity->require_otp_for_preview,
                    'otp_valid_duration_minutes' => $otpSecurity->otp_valid_duration_minutes,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to enable OTP protection', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to enable OTP protection'
            ], 500);
        }
    }

    /**
     * Disable OTP protection for a file
     */
    public function disableOtp(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file_type' => 'required|in:regular,permanent',
                'file_id' => 'required|integer',
                'otp_code' => 'required|string|size:6'
            ]);

            $user = Auth::user();
            
            // Find and verify OTP first
            if ($request->file_type === 'regular') {
                $otpSecurity = FileOtpSecurity::where('user_id', $user->id)
                    ->where('file_id', $request->file_id)
                    ->where('is_otp_enabled', DB::raw('true'))
                    ->first();
            } else {
                $otpSecurity = FileOtpSecurity::where('user_id', $user->id)
                    ->where('permanent_storage_id', $request->file_id)
                    ->where('is_otp_enabled', DB::raw('true'))
                    ->first();
            }
            
            if (!$otpSecurity) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP protection is not enabled for this file'
                ], 400);
            }
            
            // Verify OTP code before disabling
            if (!$otpSecurity->verifyOtp($request->otp_code)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired OTP code',
                    'remaining_attempts' => $otpSecurity->remaining_attempts
                ], 400);
            }
            
            // OTP verified, now disable protection
            if ($request->file_type === 'regular') {
                // Remove confidential flag from regular file
                DB::table('files')
                    ->where('id', $request->file_id)
                    ->where('user_id', $user->id)
                    ->update([
                        'is_confidential' => DB::raw('false'),
                        'confidential_enabled_at' => null,
                        'updated_at' => now()
                    ]);
            }

            // Disable OTP protection
            $otpSecurity->update(['is_otp_enabled' => DB::raw('false')]);

            return response()->json([
                'success' => true,
                'message' => 'OTP protection disabled successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to disable OTP protection', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to disable OTP protection'
            ], 500);
        }
    }

    /**
     * Send OTP to user's email
     */
    public function sendOtp(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file_type' => 'required|in:regular,permanent',
                'file_id' => 'required|integer'
            ]);

            $user = Auth::user();
            
            // Find OTP security record
            if ($request->file_type === 'regular') {
                $otpSecurity = FileOtpSecurity::where('user_id', $user->id)
                    ->where('file_id', $request->file_id)
                    ->where('is_otp_enabled', DB::raw('true'))
                    ->firstOrFail();
            } else {
                $otpSecurity = FileOtpSecurity::where('user_id', $user->id)
                    ->where('permanent_storage_id', $request->file_id)
                    ->where('is_otp_enabled', DB::raw('true'))
                    ->firstOrFail();
            }

            // Check rate limiting
            if (!$otpSecurity->canSendOtp()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please wait before requesting another OTP'
                ], 429);
            }

            // Generate OTP
            $otp = $otpSecurity->generateOtp();

            // Send email with beautiful template
            $this->sendOtpEmail($user, $otpSecurity->file_name, $otp, $otpSecurity->otp_valid_duration_minutes);

            return response()->json([
                'success' => true,
                'message' => 'OTP sent to your email address',
                'expires_in_minutes' => $otpSecurity->otp_valid_duration_minutes,
                'remaining_attempts' => $otpSecurity->max_otp_attempts
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send OTP', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP'
            ], 500);
        }
    }

    /**
     * Verify OTP code
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file_type' => 'required|in:regular,permanent',
                'file_id' => 'required|integer',
                'otp_code' => 'required|string|size:6'
            ]);

            $user = Auth::user();
            
            // Find OTP security record
            if ($request->file_type === 'regular') {
                $otpSecurity = FileOtpSecurity::where('user_id', $user->id)
                    ->where('file_id', $request->file_id)
                    ->where('is_otp_enabled', DB::raw('true'))
                    ->firstOrFail();
            } else {
                $otpSecurity = FileOtpSecurity::where('user_id', $user->id)
                    ->where('permanent_storage_id', $request->file_id)
                    ->where('is_otp_enabled', DB::raw('true'))
                    ->firstOrFail();
            }

            // Verify OTP
            $isValid = $otpSecurity->verifyOtp($request->otp_code);

            if ($isValid) {
                // Set session to allow file access for the specified duration
                $fileId = $request->file_type === 'regular' ? $request->file_id : null;
                $permanentId = $request->file_type === 'permanent' ? $request->file_id : null;
                
                $sessionTime = now();
                
                if ($fileId) {
                    session(["otp_verified_file_{$fileId}" => $sessionTime]);
                    Log::info('OTP Session Set', [
                        'file_id' => $fileId,
                        'session_key' => "otp_verified_file_{$fileId}",
                        'session_time' => $sessionTime->toISOString(),
                        'duration_minutes' => $otpSecurity->otp_valid_duration_minutes
                    ]);
                }
                if ($permanentId) {
                    session(["otp_verified_permanent_{$permanentId}" => $sessionTime]);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'OTP verified successfully',
                    'expires_in_minutes' => $otpSecurity->otp_valid_duration_minutes
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired OTP',
                    'remaining_attempts' => $otpSecurity->remaining_attempts
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Failed to verify OTP', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify OTP'
            ], 500);
        }
    }

    /**
     * Get OTP status for a file
     */
    public function getOtpStatus(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file_type' => 'required|in:regular,permanent',
                'file_id' => 'required|integer'
            ]);

            $user = Auth::user();
            
            if ($request->file_type === 'regular') {
                $otpSecurity = FileOtpSecurity::where('user_id', $user->id)
                    ->where('file_id', $request->file_id)
                    ->first();
            } else {
                $otpSecurity = FileOtpSecurity::where('user_id', $user->id)
                    ->where('permanent_storage_id', $request->file_id)
                    ->first();
            }

            if (!$otpSecurity) {
                return response()->json([
                    'success' => true,
                    'otp_enabled' => false
                ]);
            }

            return response()->json([
                'success' => true,
                'otp_enabled' => $otpSecurity->is_otp_enabled,
                'require_otp_for_download' => $otpSecurity->require_otp_for_download,
                'require_otp_for_preview' => $otpSecurity->require_otp_for_preview,
                'otp_valid_duration_minutes' => $otpSecurity->otp_valid_duration_minutes,
                'total_access_count' => $otpSecurity->total_access_count,
                'last_successful_access_at' => $otpSecurity->last_successful_access_at?->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get OTP status', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get OTP status'
            ], 500);
        }
    }

    /**
     * Send OTP email with beautiful template
     */
    private function sendOtpEmail($user, $fileName, $otp, $expiryMinutes = 10): void
    {
        try {
            // Send email using the beautiful template
            Mail::send('emails.otp-verification', [
                'user' => $user,
                'fileName' => $fileName,
                'otp' => $otp,
                'expiryMinutes' => $expiryMinutes
            ], function ($message) use ($user, $fileName) {
                $message->to($user->email, $user->name)
                        ->subject("🔐 SecureDocs - File Access OTP for {$fileName}");
            });

            Log::info('OTP Email Sent Successfully', [
                'user_email' => $user->email,
                'file_name' => $fileName,
                'otp_code' => $otp,
                'template' => 'emails.otp-verification'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send OTP email', [
                'user_email' => $user->email,
                'file_name' => $fileName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate access token for verified OTP
     */
    private function generateAccessToken($otpSecurity): string
    {
        return base64_encode(json_encode([
            'otp_security_id' => $otpSecurity->id,
            'user_id' => $otpSecurity->user_id,
            'expires_at' => now()->addMinutes(30)->timestamp,
            'signature' => hash_hmac('sha256', $otpSecurity->id . $otpSecurity->user_id, config('app.key'))
        ]));
    }
}
