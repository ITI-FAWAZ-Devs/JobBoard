<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\StripeClient;

class EmployerPaymentController extends Controller
{
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

    public function createCheckoutSession(Request $request): JsonResponse
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

        $frontendUrl = rtrim((string) config('app.frontend_url', 'http://localhost:5173'), '/');

        $session = null;
        $payment = DB::transaction(function () use ($user, $candidate, $job, $application, $amount, $currency, $stripeSecret, $frontendUrl, &$session) {
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
            $session = $stripe->checkout->sessions->create([
                'mode' => 'payment',
                'success_url' => $frontendUrl.'/payment/success/'.$application->id.'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $frontendUrl.'/payment/fail/'.$application->id,
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => $currency,
                            'product_data' => [
                                'name' => 'Unlock Candidate Contact',
                                'description' => 'Access contact details for '.($candidate->name ?? 'candidate').' applying to '.($job->title ?? 'job'),
                            ],
                            'unit_amount' => (int) round($amount * 100),
                        ],
                        'quantity' => 1,
                    ],
                ],
                'metadata' => [
                    'payment_id' => (string) $payment->id,
                    'application_id' => (string) $application->id,
                    'employer_id' => (string) $user->id,
                ],
                'client_reference_id' => (string) $application->id,
            ]);

            $payment->update([
                'stripe_session_id' => $session->id,
            ]);

            return $payment->fresh();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Stripe checkout session created.',
            'data' => [
                'session_url' => $session->url,
                'session_id' => $session->id,
            ],
        ]);
    }

    public function verifySessionStatus(Request $request, Application $application): JsonResponse
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
                'message' => 'You are not authorized to verify this payment.',
                'data' => null,
            ], 403);
        }

        $payment = Payment::query()
            ->where('application_id', $application->id)
            ->where('status', 'paid')
            ->first();

        $isPaid = $payment !== null || $application->status === 'paid';

        return response()->json([
            'status' => 'success',
            'message' => $isPaid ? 'Payment confirmed.' : 'Payment not yet confirmed.',
            'data' => [
                'paid' => $isPaid,
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

    private function contactPrice(): float
    {
        return (float) config('services.payments.contact_price', 49.00);
    }
}
