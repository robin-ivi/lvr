<?php

use App\Models\Payment;
use App\Models\PaymentFailure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.razorpay.key', 'rzp_test_key');
    config()->set('services.razorpay.secret', 'rzp_test_secret');
});

test('payment form page loads', function () {
    $response = $this->get(route('payments.create'));

    $response->assertOk();
});

test('it creates a razorpay order', function () {
    Http::fake([
        'https://api.razorpay.com/v1/orders' => Http::response([
            'id' => 'order_test_123',
            'amount' => 49900,
            'currency' => 'INR',
        ], 200),
    ]);

    $response = $this->postJson(route('payments.order'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '9876543210',
        'purpose' => 'Booking',
        'amount' => 499,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('order.id', 'order_test_123')
        ->assertJsonPath('order.amount', 49900);
});

test('it verifies razorpay payment and stores form data', function () {
    $orderId = 'order_test_123';
    $paymentId = 'pay_test_456';
    $signature = hash_hmac('sha256', $orderId.'|'.$paymentId, 'rzp_test_secret');

    $response = $this->postJson(route('payments.verify'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '9876543210',
        'purpose' => 'Booking',
        'amount' => 499,
        'razorpay_order_id' => $orderId,
        'razorpay_payment_id' => $paymentId,
        'razorpay_signature' => $signature,
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('payments', [
        'email' => 'test@example.com',
        'razorpay_order_id' => $orderId,
        'razorpay_payment_id' => $paymentId,
        'status' => 'paid',
    ]);

    expect(Payment::first())->not->toBeNull();
});

test('it rejects an invalid razorpay signature', function () {
    $response = $this->postJson(route('payments.verify'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '9876543210',
        'purpose' => 'Booking',
        'amount' => 499,
        'razorpay_order_id' => 'order_test_123',
        'razorpay_payment_id' => 'pay_test_456',
        'razorpay_signature' => 'invalid-signature',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors('payment');

    $this->assertDatabaseCount('payments', 0);
});

test('it logs a failed razorpay payment attempt', function () {
    Log::spy();

    $response = $this->postJson(route('payments.failure'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '9876543210',
        'purpose' => 'Booking',
        'amount' => 500,
        'currency' => 'INR',
        'error' => [
            'code' => 'BAD_REQUEST_ERROR',
            'description' => 'Payment could not be completed',
            'source' => 'customer',
            'step' => 'payment_authentication',
            'reason' => 'payment_failed',
            'metadata' => [
                'order_id' => 'order_test_123',
                'payment_id' => 'pay_test_456',
            ],
        ],
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Payment failure logged.');

    $this->assertDatabaseHas('payment_failures', [
        'email' => 'test@example.com',
        'error_code' => 'BAD_REQUEST_ERROR',
        'razorpay_order_id' => 'order_test_123',
        'razorpay_payment_id' => 'pay_test_456',
    ]);

    expect(PaymentFailure::first())->not->toBeNull();

    Log::shouldHaveReceived('warning')->once();
});
