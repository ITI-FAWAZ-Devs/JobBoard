<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Stripe\StripeClient;

class EmployerPaymentController extends Controller
{
    public function createStripeIntent(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'job_id' => ['required', 'integer', 'exists:job_listings,id'],
            'candidate_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $employerProfile = $user->employerProfile;

        if (! $employerProfile) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employer profile not found.',
                'data' => null,
            ], 404);
        }

        $job = JobListing::query()
            ->where('id', $validated['job_id'])
            ->where('employer_profile_id', $employerProfile->id)
            ->first();

        if (! $job) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job not found for this employer.',
                'data' => null,
            ], 404);
        }

        $candidate = User::query()
            ->where('id', $validated['candidate_id'])
            ->where('role', 'candidate')
            ->first();

        if (! $candidate) {
            return response()->json([
                'status' => 'error',
                'message' => 'Candidate not found.',
                'data' => null,
            ], 404);
        }

        $existingPaid = Payment::query()
            ->where('employer_id', $user->id)
            ->where('candidate_id', $candidate->id)
            ->where('job_id', $job->id)
            ->where('status', 'paid')
            ->first();

        if ($existingPaid) {
            return response()->json([
                'status' => 'error',
                'message' => 'Candidate contact already unlocked for this job.',
                'data' => null,
            ], 409);
        }

        $amount = $this->contactPrice();
        $currency = strtolower((string) config('services.payments.currency', 'usd'));
        $stripeSecret = config('services.stripe.secret');

        if (! $stripeSecret) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stripe is not configured.',
                'data' => null,
            ], 500);
        }

        $payment = Payment::create([
            'employer_id' => $user->id,
            'candidate_id' => $candidate->id,
            'job_id' => $job->id,
            'provider' => 'stripe',
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
        ]);

        $stripe = new StripeClient($stripeSecret);
        $intent = $stripe->paymentIntents->create([
            'amount' => (int) round($amount * 100),
            'currency' => $currency,
            'metadata' => [
                'payment_id' => $payment->id,
                'job_id' => $job->id,
                'candidate_id' => $candidate->id,
                'employer_id' => $user->id,
            ],
        ]);

        $payment->update([
            'stripe_payment_intent_id' => $intent->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Stripe payment intent created.',
            'data' => [
                'payment' => $this->formatPayment($payment->fresh()),
                'client_secret' => $intent->client_secret,
            ],
        ]);
    }

    public function createPayPalOrder(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'job_id' => ['required', 'integer', 'exists:job_listings,id'],
            'candidate_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $employerProfile = $user->employerProfile;

        if (! $employerProfile) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employer profile not found.',
                'data' => null,
            ], 404);
        }

        $job = JobListing::query()
            ->where('id', $validated['job_id'])
            ->where('employer_profile_id', $employerProfile->id)
            ->first();

        if (! $job) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job not found for this employer.',
                'data' => null,
            ], 404);
        }

        $candidate = User::query()
            ->where('id', $validated['candidate_id'])
            ->where('role', 'candidate')
            ->first();

        if (! $candidate) {
            return response()->json([
                'status' => 'error',
                'message' => 'Candidate not found.',
                'data' => null,
            ], 404);
        }

        $existingPaid = Payment::query()
            ->where('employer_id', $user->id)
            ->where('candidate_id', $candidate->id)
            ->where('job_id', $job->id)
            ->where('status', 'paid')
            ->first();

        if ($existingPaid) {
            return response()->json([
                'status' => 'error',
                'message' => 'Candidate contact already unlocked for this job.',
                'data' => null,
            ], 409);
        }

        $amount = $this->contactPrice();
        $currency = strtoupper((string) config('services.payments.currency', 'usd'));
        $baseUrl = rtrim((string) config('services.paypal.base_url', 'https://api-m.sandbox.paypal.com'), '/');

        $accessToken = $this->getPayPalAccessToken();

        if (! $accessToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'PayPal is not configured.',
                'data' => null,
            ], 500);
        }

        $payment = Payment::create([
            'employer_id' => $user->id,
            'candidate_id' => $candidate->id,
            'job_id' => $job->id,
            'provider' => 'paypal',
            'amount' => $amount,
            'currency' => strtolower($currency),
            'status' => 'pending',
        ]);

        $response = Http::withToken($accessToken)
            ->post($baseUrl.'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => (string) $payment->id,
                        'amount' => [
                            'currency_code' => $currency,
                            'value' => number_format($amount, 2, '.', ''),
                        ],
                    ],
                ],
                'application_context' => [
                    'return_url' => rtrim((string) config('app.frontend_url'), '/').'/employer/checkout?status=success',
                    'cancel_url' => rtrim((string) config('app.frontend_url'), '/').'/employer/checkout?status=cancel',
                ],
            ]);

        if (! $response->successful()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create PayPal order.',
                'data' => $response->json(),
            ], 422);
        }

        $orderId = $response->json('id');
        $approvalUrl = collect($response->json('links', []))
            ->firstWhere('rel', 'approve')['href'] ?? null;

        $payment->update([
            'paypal_order_id' => $orderId,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'PayPal order created.',
            'data' => [
                'payment' => $this->formatPayment($payment->fresh()),
                'order_id' => $orderId,
                'approval_url' => $approvalUrl,
            ],
        ]);
    }

    private function contactPrice(): float
    {
        return (float) config('services.payments.contact_price', 49.00);
    }

    private function getPayPalAccessToken(): ?string
    {
        $clientId = config('services.paypal.client_id');
        $clientSecret = config('services.paypal.client_secret');

        if (! $clientId || ! $clientSecret) {
            return null;
        }

        $baseUrl = rtrim((string) config('services.paypal.base_url', 'https://api-m.sandbox.paypal.com'), '/');

        $response = Http::withBasicAuth($clientId, $clientSecret)
            ->asForm()
            ->post($baseUrl.'/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            return null;
        }

        return $response->json('access_token');
    }

    private function formatPayment(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'provider' => $payment->provider,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'status' => $payment->status,
            'job_id' => $payment->job_id,
            'candidate_id' => $payment->candidate_id,
            'employer_id' => $payment->employer_id,
            'created_at' => $payment->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
