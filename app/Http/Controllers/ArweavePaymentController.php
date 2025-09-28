<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\PaymentTransaction;
use App\Services\ArweavePaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class ArweavePaymentController extends Controller
{
    protected ArweavePaymentService $paymentService;

    public function __construct(ArweavePaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Get pricing for file upload
     */
    public function getUploadPricing(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'file_size' => 'required|integer|min:1|max:104857600', // Max 100MB
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file size',
                    'errors' => $validator->errors()
                ], 422);
            }

            $fileSize = $request->integer('file_size');
            $pricing = $this->paymentService->calculateTotalCost($fileSize);

            return response()->json([
                'success' => true,
                'pricing' => $pricing,
                'supported_currencies' => config('arweave-payments.wallet.supported_currencies'),
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get upload pricing', [
                'error' => $e->getMessage(),
                'file_size' => $request->get('file_size')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate pricing: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process payment and upload file
     */
    public function processPayment(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'file_id' => 'required|integer|exists:files,id',
                'payment_method' => 'required|string|in:crypto,fiat',
                'currency' => 'required|string|max:10',
                'amount' => 'required|numeric|min:0',
                'amount_usd' => 'required|numeric|min:0',
                'service_fee_usd' => 'required|numeric|min:0',
                'wallet_address' => 'nullable|string|max:255',
                'transaction_hash' => 'required_if:payment_method,crypto|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment data',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $file = File::where('id', $request->integer('file_id'))
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Check if file is already uploaded to Arweave
            if ($file->is_blockchain_stored) {
                return response()->json([
                    'success' => false,
                    'message' => 'File is already stored on blockchain'
                ], 400);
            }

            $paymentData = [
                'method' => $request->string('payment_method'),
                'currency' => $request->string('currency'),
                'amount' => $request->float('amount'),
                'amount_usd' => $request->float('amount_usd'),
                'service_fee_usd' => $request->float('service_fee_usd'),
                'wallet_address' => $request->string('wallet_address'),
                'tx_hash' => $request->string('transaction_hash'),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
            ];

            $result = $this->paymentService->processPaymentAndUpload($file, $user, $paymentData);

            if ($result['success']) {
                // Update file record
                $file->update([
                    'is_blockchain_stored' => true,
                    'blockchain_provider' => 'arweave',
                    'arweave_tx_id' => $result['arweave_tx_id'],
                    'arweave_url' => $result['arweave_url'],
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment processed and file uploaded successfully',
                    'data' => [
                        'payment_id' => $result['payment_id'],
                        'arweave_tx_id' => $result['arweave_tx_id'],
                        'arweave_url' => $result['arweave_url'],
                        'total_cost_usd' => $result['total_cost_usd'],
                        'service_fee_usd' => $result['service_fee_usd'],
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Payment processing failed'
                ], 500);
            }

        } catch (Exception $e) {
            Log::error('Payment processing failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'file_id' => $request->get('file_id')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment history for user
     */
    public function getPaymentHistory(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $payments = PaymentTransaction::where('user_id', $user->id)
                ->with(['file', 'arweaveTransaction'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'payments' => $payments->items(),
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get payment history', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load payment history'
            ], 500);
        }
    }

    /**
     * Get payment details
     */
    public function getPaymentDetails(Request $request, int $paymentId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $payment = PaymentTransaction::where('id', $paymentId)
                ->where('user_id', $user->id)
                ->with(['file', 'arweaveTransaction'])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'payment' => [
                    'id' => $payment->id,
                    'file_name' => $payment->file->file_name ?? 'Unknown',
                    'payment_method' => $payment->payment_method,
                    'currency' => $payment->currency,
                    'amount' => $payment->amount,
                    'amount_usd' => $payment->amount_usd,
                    'service_fee_usd' => $payment->service_fee_usd,
                    'total_amount_usd' => $payment->total_amount_usd,
                    'status' => $payment->status,
                    'transaction_hash' => $payment->transaction_hash,
                    'wallet_address' => $payment->wallet_address,
                    'arweave_tx_id' => $payment->arweave_tx_id,
                    'arweave_url' => $payment->arweaveTransaction->gateway_url ?? null,
                    'created_at' => $payment->created_at,
                    'completed_at' => $payment->completed_at,
                    'formatted_amount' => $payment->formatted_amount,
                    'formatted_total_amount' => $payment->formatted_total_amount,
                    'status_color' => $payment->status_color,
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get payment details', [
                'error' => $e->getMessage(),
                'payment_id' => $paymentId,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }
    }

    /**
     * Verify blockchain transaction
     */
    public function verifyTransaction(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'transaction_hash' => 'required|string|max:255',
                'currency' => 'required|string|max:10',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid transaction data',
                    'errors' => $validator->errors()
                ], 422);
            }

            // TODO: Implement actual blockchain verification
            // This would involve calling the appropriate blockchain API
            // to verify the transaction exists and has the correct amount

            $txHash = $request->string('transaction_hash');
            $currency = $request->string('currency');

            // Placeholder verification (replace with real implementation)
            $verified = $this->verifyBlockchainTransaction($txHash, $currency);

            return response()->json([
                'success' => true,
                'verified' => $verified,
                'transaction_hash' => $txHash,
                'message' => $verified ? 'Transaction verified' : 'Transaction not found or invalid'
            ]);

        } catch (Exception $e) {
            Log::error('Transaction verification failed', [
                'error' => $e->getMessage(),
                'tx_hash' => $request->get('transaction_hash')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Verification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Placeholder for blockchain transaction verification
     * TODO: Implement real verification using blockchain APIs
     */
    protected function verifyBlockchainTransaction(string $txHash, string $currency): bool
    {
        // This is a placeholder - in production you would:
        // 1. Use appropriate blockchain API (Etherscan, Polygonscan, etc.)
        // 2. Verify transaction exists and is confirmed
        // 3. Check transaction amount matches expected payment
        // 4. Verify recipient address is correct
        
        Log::info('Verifying blockchain transaction', [
            'tx_hash' => $txHash,
            'currency' => $currency
        ]);

        // For demo purposes, return true
        // In production, implement proper verification
        return true;
    }
}
