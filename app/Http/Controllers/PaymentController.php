<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentFailure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function create()
    {
        return inertia('Payments/Create', [
            'razorpayKey' => config('services.razorpay.key'),
            'currency' => 'INR',
            'merchantName' => config('services.razorpay.company'),
            'description' => config('services.razorpay.description'),
        ]);
    }

    public function order(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'purpose' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $this->ensureRazorpayCredentials();

        $amountInPaise = (int) round(((float) $validated['amount']) * 100);

        $response = Http::withBasicAuth(
            (string) config('services.razorpay.key'),
            (string) config('services.razorpay.secret'),
        )
            ->acceptJson()
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'receipt' => 'rcpt_'.Str::ulid(),
                'notes' => [
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'purpose' => $validated['purpose'],
                ],
            ]);

        if ($response->failed()) {
            throw ValidationException::withMessages([
                'payment' => 'Unable to create Razorpay order right now. Please try again.',
            ]);
        }

        return response()->json([
            'order' => $response->json(),
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'purpose' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        $this->ensureRazorpayCredentials();

        $generatedSignature = hash_hmac(
            'sha256',
            $validated['razorpay_order_id'].'|'.$validated['razorpay_payment_id'],
            (string) config('services.razorpay.secret'),
        );

        if (! hash_equals($generatedSignature, $validated['razorpay_signature'])) {
            throw ValidationException::withMessages([
                'payment' => 'Payment signature verification failed.',
            ]);
        }

        $payment = Payment::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'purpose' => $validated['purpose'],
            'amount' => $validated['amount'],
            'currency' => 'INR',
            'status' => 'paid',
            'razorpay_order_id' => $validated['razorpay_order_id'],
            'razorpay_payment_id' => $validated['razorpay_payment_id'],
            'razorpay_signature' => $validated['razorpay_signature'],
            'notes' => [
                'source' => 'payment-form',
            ],
            'paid_at' => now(),
        ]);

        return response()->json([
            'message' => 'Payment completed and form data stored successfully.',
            'payment' => $payment,
        ]);
    }

    public function failure(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'purpose' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
            'currency' => ['nullable', 'string', 'size:3'],
            'error' => ['nullable', 'array'],
            'error.code' => ['nullable', 'string'],
            'error.description' => ['nullable', 'string'],
            'error.source' => ['nullable', 'string'],
            'error.step' => ['nullable', 'string'],
            'error.reason' => ['nullable', 'string'],
            'error.metadata' => ['nullable', 'array'],
            'error.metadata.order_id' => ['nullable', 'string'],
            'error.metadata.payment_id' => ['nullable', 'string'],
        ]);

        $failure = PaymentFailure::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'purpose' => $validated['purpose'],
            'amount' => $validated['amount'],
            'currency' => $validated['currency'] ?? 'INR',
            'razorpay_order_id' => data_get($validated, 'error.metadata.order_id'),
            'razorpay_payment_id' => data_get($validated, 'error.metadata.payment_id'),
            'error_code' => data_get($validated, 'error.code'),
            'error_description' => data_get($validated, 'error.description'),
            'error_source' => data_get($validated, 'error.source'),
            'error_step' => data_get($validated, 'error.step'),
            'error_reason' => data_get($validated, 'error.reason'),
            'payload' => $validated,
            'failed_at' => now(),
        ]);

        Log::warning('Razorpay payment failed', [
            'payment_failure_id' => $failure->id,
            'email' => $failure->email,
            'amount' => $failure->amount,
            'order_id' => $failure->razorpay_order_id,
            'payment_id' => $failure->razorpay_payment_id,
            'error_code' => $failure->error_code,
            'error_description' => $failure->error_description,
            'error_reason' => $failure->error_reason,
        ]);

        return response()->json([
            'message' => 'Payment failure logged.',
            'failure_id' => $failure->id,
        ]);
    }

    protected function ensureRazorpayCredentials(): void
    {
        if (! config('services.razorpay.key') || ! config('services.razorpay.secret')) {
            throw ValidationException::withMessages([
                'payment' => 'Razorpay credentials are missing. Set RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET in your .env file.',
            ]);
        }
    }
}
