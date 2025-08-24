<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\SecurityPolicy;
use App\Models\SecurityViolation;
use App\Models\TrustedDevice;
use App\Models\FileEncryption;
use App\Models\DlpScanResult;
use App\Models\File;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SecurityController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // ===== SECURITY POLICIES =====

    /**
     * Get security policies
     */
    public function getPolicies(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            $query = SecurityPolicy::with(['createdBy', 'updatedBy'])
                ->where(function($q) use ($user) {
                    $q->where('scope', 'global')
                      ->orWhere('scope_id', $user->id)
                      ->orWhere('scope_id', $user->current_team_id);
                });

            // Apply filters
            if ($request->filled('type')) {
                $query->where('policy_type', $request->type);
            }

            if ($request->filled('status')) {
                $query->where('is_active', $request->status === 'active');
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                      ->orWhere('description', 'ILIKE', "%{$search}%");
                });
            }

            $policies = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $policies->items(),
                'meta' => [
                    'current_page' => $policies->currentPage(),
                    'last_page' => $policies->lastPage(),
                    'total' => $policies->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch security policies: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new security policy
     */
    public function createPolicy(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'policy_type' => ['required', Rule::in([
                    SecurityPolicy::TYPE_ACCESS_CONTROL,
                    SecurityPolicy::TYPE_DLP,
                    SecurityPolicy::TYPE_ENCRYPTION,
                    SecurityPolicy::TYPE_AUDIT
                ])],
                'scope' => ['required', Rule::in(['global', 'team', 'user', 'folder', 'file'])],
                'scope_id' => 'nullable|integer',
                'rules' => 'required|array',
                'actions' => 'required|array',
                'description' => 'nullable|string',
                'priority' => 'integer|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $policy = SecurityPolicy::create([
                'name' => $request->name,
                'policy_type' => $request->policy_type,
                'scope' => $request->scope,
                'scope_id' => $request->scope_id,
                'rules' => $request->rules,
                'actions' => $request->actions,
                'description' => $request->description,
                'priority' => $request->get('priority', 50),
                'created_by_user_id' => auth()->id(),
                'updated_by_user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Security policy created successfully',
                'data' => $policy->load(['createdBy', 'updatedBy'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create security policy: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update security policy
     */
    public function updatePolicy(Request $request, SecurityPolicy $policy): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'string|max:255',
                'rules' => 'array',
                'actions' => 'array',
                'description' => 'nullable|string',
                'priority' => 'integer|min:1|max:100',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $policy->update(array_merge(
                $request->only(['name', 'rules', 'actions', 'description', 'priority', 'is_active']),
                ['updated_by_user_id' => auth()->id()]
            ));

            return response()->json([
                'success' => true,
                'message' => 'Security policy updated successfully',
                'data' => $policy->fresh(['createdBy', 'updatedBy'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update security policy: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete security policy
     */
    public function deletePolicy(SecurityPolicy $policy): JsonResponse
    {
        try {
            $policy->delete();

            return response()->json([
                'success' => true,
                'message' => 'Security policy deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete security policy: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===== SECURITY VIOLATIONS =====

    /**
     * Get security violations
     */
    public function getViolations(Request $request): JsonResponse
    {
        try {
            $query = SecurityViolation::with(['user', 'file', 'policy', 'resolvedBy']);

            // Apply filters
            if ($request->filled('severity')) {
                $query->where('severity', $request->severity);
            }

            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('date_from')) {
                $query->where('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
            }

            $violations = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $violations->items(),
                'meta' => [
                    'current_page' => $violations->currentPage(),
                    'last_page' => $violations->lastPage(),
                    'total' => $violations->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch security violations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resolve security violation
     */
    public function resolveViolation(Request $request, SecurityViolation $violation): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'resolution_notes' => 'required|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $violation->resolve(auth()->user(), $request->resolution_notes);

            return response()->json([
                'success' => true,
                'message' => 'Security violation resolved successfully',
                'data' => $violation->fresh(['user', 'file', 'policy', 'resolvedBy'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resolve security violation: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===== TRUSTED DEVICES =====

    /**
     * Get trusted devices
     */
    public function getTrustedDevices(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            $query = TrustedDevice::forUser($user->id)
                ->with(['trustedBy', 'revokedBy']);

            // Apply filters
            if ($request->filled('status')) {
                if ($request->status === 'active') {
                    $query->active();
                } elseif ($request->status === 'revoked') {
                    $query->where('is_active', false);
                } elseif ($request->status === 'expired') {
                    $query->expired();
                }
            }

            if ($request->filled('trust_level')) {
                $query->byTrustLevel($request->trust_level);
            }

            $devices = $query->orderBy('last_used_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $devices->items(),
                'meta' => [
                    'current_page' => $devices->currentPage(),
                    'last_page' => $devices->lastPage(),
                    'total' => $devices->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch trusted devices: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Trust a device
     */
    public function trustDevice(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'device_fingerprint' => 'required|string',
                'device_name' => 'required|string|max:255',
                'trust_level' => ['required', Rule::in([
                    TrustedDevice::TRUST_LIMITED,
                    TrustedDevice::TRUST_STANDARD,
                    TrustedDevice::TRUST_HIGH
                ])],
                'expires_days' => 'integer|min:1|max:365',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            
            // Check if device already exists
            $existingDevice = TrustedDevice::where('user_id', $user->id)
                ->where('device_fingerprint', $request->device_fingerprint)
                ->first();

            if ($existingDevice) {
                $existingDevice->trust($user, $request->trust_level);
                $device = $existingDevice;
            } else {
                $device = TrustedDevice::create([
                    'user_id' => $user->id,
                    'device_name' => $request->device_name,
                    'device_fingerprint' => $request->device_fingerprint,
                    'device_type' => TrustedDevice::detectDeviceType([]),
                    'trusted_by_user_id' => $user->id,
                    'trust_level' => $request->trust_level,
                    'expires_at' => now()->addDays($request->get('expires_days', 90)),
                    'last_used_at' => now(),
                    'last_ip_address' => request()->ip(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Device trusted successfully',
                'data' => $device->load(['trustedBy', 'revokedBy'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to trust device: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revoke trusted device
     */
    public function revokeDevice(Request $request, TrustedDevice $device): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check permission
            if ($device->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to revoke this device'
                ], 403);
            }

            $device->revoke(auth()->user(), $request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Device revoked successfully',
                'data' => $device->fresh(['trustedBy', 'revokedBy'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke device: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===== FILE ENCRYPTION =====

    /**
     * Get file encryption info
     */
    public function getFileEncryption(Request $request): JsonResponse
    {
        try {
            $query = FileEncryption::with(['file', 'encryptedBy', 'lastDecryptedBy']);

            // Filter by user's files only
            $query->whereHas('file', function($q) {
                $q->where('user_id', auth()->id());
            });

            if ($request->filled('access_level')) {
                $query->byAccessLevel($request->access_level);
            }

            if ($request->filled('needs_rotation')) {
                if ($request->needs_rotation === 'true') {
                    $query->needsKeyRotation();
                }
            }

            $encryption = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $encryption->items(),
                'meta' => [
                    'current_page' => $encryption->currentPage(),
                    'last_page' => $encryption->lastPage(),
                    'total' => $encryption->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch file encryption data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Encrypt file
     */
    public function encryptFile(Request $request, File $file): JsonResponse
    {
        try {
            // Check permission
            if ($file->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to encrypt this file'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'access_level' => ['required', Rule::in([
                    FileEncryption::ACCESS_PUBLIC,
                    FileEncryption::ACCESS_INTERNAL,
                    FileEncryption::ACCESS_CONFIDENTIAL,
                    FileEncryption::ACCESS_RESTRICTED,
                    FileEncryption::ACCESS_TOP_SECRET
                ])],
                'algorithm' => ['nullable', Rule::in([
                    FileEncryption::ALGORITHM_AES_256,
                    FileEncryption::ALGORITHM_AES_192,
                    FileEncryption::ALGORITHM_AES_128
                ])],
                'key_rotation_days' => 'integer|min:30|max:365',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if file is already encrypted
            $existingEncryption = FileEncryption::where('file_id', $file->id)->first();
            if ($existingEncryption) {
                return response()->json([
                    'success' => false,
                    'message' => 'File is already encrypted'
                ], 422);
            }

            $encryption = FileEncryption::createForFile($file, auth()->user(), [
                'access_level' => $request->access_level,
                'algorithm' => $request->get('algorithm', FileEncryption::ALGORITHM_AES_256),
                'key_rotation_days' => $request->get('key_rotation_days', 90),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File encrypted successfully',
                'data' => $encryption->load(['file', 'encryptedBy'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to encrypt file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rotate encryption key
     */
    public function rotateEncryptionKey(FileEncryption $encryption): JsonResponse
    {
        try {
            // Check permission
            if ($encryption->file->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to rotate key for this file'
                ], 403);
            }

            $success = $encryption->rotateKey();

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Encryption key rotated successfully',
                    'data' => $encryption->fresh(['file', 'encryptedBy'])
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to rotate encryption key'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to rotate encryption key: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===== DLP SCAN RESULTS =====

    /**
     * Get DLP scan results
     */
    public function getDlpScans(Request $request): JsonResponse
    {
        try {
            $query = DlpScanResult::with(['file', 'policy', 'reviewedBy']);

            // Filter by user's files only
            $query->whereHas('file', function($q) {
                $q->where('user_id', auth()->id());
            });

            if ($request->filled('risk_level')) {
                $query->byRiskLevel($request->risk_level);
            }

            if ($request->filled('scan_status')) {
                $query->where('scan_status', $request->scan_status);
            }

            if ($request->filled('needs_review')) {
                if ($request->needs_review === 'true') {
                    $query->needsReview();
                }
            }

            $scans = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $scans->items(),
                'meta' => [
                    'current_page' => $scans->currentPage(),
                    'last_page' => $scans->lastPage(),
                    'total' => $scans->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch DLP scan results: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Review DLP scan result
     */
    public function reviewDlpScan(Request $request, DlpScanResult $scan): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'action' => ['required', Rule::in(['approve', 'reject', 'requires_action'])],
                'notes' => 'required|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check permission
            if ($scan->file->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to review this scan'
                ], 403);
            }

            $user = auth()->user();
            $success = match($request->action) {
                'approve' => $scan->approve($user, $request->notes),
                'reject' => $scan->reject($user, $request->notes),
                'requires_action' => $scan->requiresAction($user, $request->notes),
            };

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'DLP scan reviewed successfully',
                    'data' => $scan->fresh(['file', 'policy', 'reviewedBy'])
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to review DLP scan'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to review DLP scan: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===== SECURITY DASHBOARD =====

    /**
     * Get security dashboard statistics
     */
    public function getSecurityStats(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            // Get counts for user's data
            $stats = [
                'violations' => [
                    'total' => SecurityViolation::whereHas('file', function($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })->count(),
                    'unresolved' => SecurityViolation::whereHas('file', function($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })->where('status', SecurityViolation::STATUS_OPEN)->count(),
                    'high_severity' => SecurityViolation::whereHas('file', function($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })->whereIn('severity', [SecurityViolation::SEVERITY_HIGH, SecurityViolation::SEVERITY_CRITICAL])->count(),
                ],
                'trusted_devices' => [
                    'total' => TrustedDevice::forUser($user->id)->count(),
                    'active' => TrustedDevice::forUser($user->id)->active()->count(),
                    'need_renewal' => TrustedDevice::forUser($user->id)->where('expires_at', '<=', now()->addDays(30))->count(),
                ],
                'encryption' => [
                    'encrypted_files' => FileEncryption::whereHas('file', function($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })->count(),
                    'need_key_rotation' => FileEncryption::whereHas('file', function($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })->needsKeyRotation()->count(),
                    'high_security' => FileEncryption::whereHas('file', function($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })->highSecurity()->count(),
                ],
                'dlp_scans' => [
                    'total' => DlpScanResult::whereHas('file', function($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })->count(),
                    'high_risk' => DlpScanResult::whereHas('file', function($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })->highRisk()->count(),
                    'need_review' => DlpScanResult::whereHas('file', function($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })->needsReview()->count(),
                ],
                'policies' => [
                    'total' => SecurityPolicy::where(function($q) use ($user) {
                        $q->where('scope', 'global')
                          ->orWhere('scope_id', $user->id)
                          ->orWhere('scope_id', $user->current_team_id);
                    })->count(),
                    'active' => SecurityPolicy::where(function($q) use ($user) {
                        $q->where('scope', 'global')
                          ->orWhere('scope_id', $user->id)
                          ->orWhere('scope_id', $user->current_team_id);
                    })->where('is_active', true)->count(),
                ],
            ];

            // Get recent violations
            $recentViolations = SecurityViolation::with(['file', 'user'])
                ->whereHas('file', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'recent_violations' => $recentViolations
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch security statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}
