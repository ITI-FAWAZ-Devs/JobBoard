<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JobListing;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $payment = DB::transaction(function () use ($user, $candidate, $job, $amount, $currency, $stripeSecret) {
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

            return $payment->fresh();
        });

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

        $result = DB::transaction(function () use ($user, $candidate, $job, $amount, $currency, $baseUrl, $accessToken) {
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
                    'return_url' => rtrim((string) config('app.frontend_url'), '/').'/payment/success/'.$payment->application_id,
                    'cancel_url' => rtrim((string) config('app.frontend_url'), '/').'/payment/checkout/'.$payment->application_id,
                    ],
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException('PayPal order creation failed: '.$response->body());
            }

            $orderId = $response->json('id');
            $approvalUrl = collect($response->json('links', []))
                ->firstWhere('rel', 'approve')['href'] ?? null;

            $payment->update([
                'paypal_order_id' => $orderId,
            ]);

            return [
                'payment' => $payment->fresh(),
                'order_id' => $orderId,
                'approval_url' => $approvalUrl,
            ];
        });

        $payment = $result['payment'];
        $orderId = $result['order_id'];
        $approvalUrl = $result['approval_url'];

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

    public function getCheckout(Request $request, Application $application): JsonResponse
    {
        $employer = $request->user()->employerProfile;

        if (! $employer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employer profile not found.',
                'data' => null,
            ], 404);
        }

        $job = $application->jobListing;

        if (! $job || $job->employer_profile_id !== $employer->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to view this checkout details.',
                'data' => null,
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Checkout details retrieved.',
            'data' => [
                'id' => $application->id,
                'candidate_name' => $application->candidateProfile?->user?->name,
                'job_title' => $job->title,
                'amount' => $this->contactPrice(),
                'currency' => strtoupper((string) config('services.payments.currency', 'usd')),
                'status' => $application->status,
            ],
        ]);
    }

    public function payStripe(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'application_id' => ['required', 'integer', 'exists:applications,id'],
        ]);

        $employerProfile = $user->employerProfile;

        if (! $employerProfile) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employer profile not found.',
                'data' => null,
            ], 404);
        }

        $application = Application::with(['jobListing', 'candidateProfile.user'])->find($validated['application_id']);
        $job = $application->jobListing;

        if (! $job || $job->employer_profile_id !== $employerProfile->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to pay for this application.',
                'data' => null,
            ], 403);
        }

        $candidate = $application->candidateProfile?->user;

        if (! $candidate) {
            return response()->json([
                'status' => 'error',
                'message' => 'Candidate not found.',
                'data' => null,
            ], 404);
        }

        $existingPaid = Payment::query()
            ->where('application_id', $application->id)
            ->where('status', 'paid')
            ->first();

        if ($existingPaid) {
            return response()->json([
                'status' => 'error',
                'message' => 'Candidate contact already unlocked for this application.',
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

        $intent = null;
        $payment = DB::transaction(function () use ($user, $candidate, $job, $application, $amount, $currency, $stripeSecret, &$intent) {
            $payment = Payment::create([
                'employer_id' => $user->id,
                'candidate_id' => $candidate->id,
                'job_id' => $job->id,
                'application_id' => $application->id,
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
                    'application_id' => $application->id,
                    'job_id' => $job->id,
                    'candidate_id' => $candidate->id,
                    'employer_id' => $user->id,
                ],
            ]);

            $payment->update([
                'stripe_payment_intent_id' => $intent->id,
            ]);

            return $payment->fresh();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Stripe payment intent created.',
            'data' => [
                'client_secret' => $intent->client_secret,
            ],
        ]);
    }

    public function payPayPal(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'application_id' => ['required', 'integer', 'exists:applications,id'],
        ]);

        $employerProfile = $user->employerProfile;

        if (! $employerProfile) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employer profile not found.',
                'data' => null,
            ], 404);
        }

        $application = Application::with(['jobListing', 'candidateProfile.user'])->find($validated['application_id']);
        $job = $application->jobListing;

        if (! $job || $job->employer_profile_id !== $employerProfile->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to pay for this application.',
                'data' => null,
            ], 403);
        }

        $candidate = $application->candidateProfile?->user;

        if (! $candidate) {
            return response()->json([
                'status' => 'error',
                'message' => 'Candidate not found.',
                'data' => null,
            ], 404);
        }

        $existingPaid = Payment::query()
            ->where('application_id', $application->id)
            ->where('status', 'paid')
            ->first();

        if ($existingPaid) {
            return response()->json([
                'status' => 'error',
                'message' => 'Candidate contact already unlocked for this application.',
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

        $result = DB::transaction(function () use ($user, $candidate, $job, $application, $amount, $currency, $baseUrl, $accessToken) {
            $payment = Payment::create([
                'employer_id' => $user->id,
                'candidate_id' => $candidate->id,
                'job_id' => $job->id,
                'application_id' => $application->id,
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
                        'return_url' => rtrim((string) config('app.frontend_url'), '/').'/payment/success/'.$application->id,
                        'cancel_url' => rtrim((string) config('app.frontend_url'), '/').'/payment/checkout/'.$application->id,
                    ],
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException('PayPal order creation failed: '.$response->body());
            }

            $orderId = $response->json('id');
            $approvalUrl = collect($response->json('links', []))
                ->firstWhere('rel', 'approve')['href'] ?? null;

            $payment->update([
                'paypal_order_id' => $orderId,
            ]);

            return [
                'payment' => $payment->fresh(),
                'order_id' => $orderId,
                'approval_url' => $approvalUrl,
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'PayPal order created.',
            'data' => [
                'approve_url' => $result['approval_url'],
            ],
        ]);
    }

    public function getContact(Request $request, Application $application): JsonResponse
    {
        $user = $request->user();
        $employerProfile = $user->employerProfile;

        if (! $employerProfile) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employer profile not found.',
                'data' => null,
            ], 404);
        }

        $job = $application->jobListing;

        if (! $job || $job->employer_profile_id !== $employerProfile->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to view contact details for this application.',
                'data' => null,
            ], 403);
        }

        $paid = Payment::query()
            ->where('application_id', $application->id)
            ->where('status', 'paid')
            ->exists();

        if (! $paid && $application->status !== 'paid') {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment required to reveal contact details.',
                'data' => null,
            ], 403);
        }

        $candidate = $application->candidateProfile?->user;

        if (! $candidate) {
            return response()->json([
                'status' => 'error',
                'message' => 'Candidate profile not found.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Contact details unlocked.',
            'data' => [
                'email' => $candidate->email,
                'phone' => $application->candidateProfile?->phone,
            ],
        ]);
    }
}
