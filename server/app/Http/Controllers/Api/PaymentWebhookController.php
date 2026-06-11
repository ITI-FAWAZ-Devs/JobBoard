<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class PaymentWebhookController extends Controller
{
    public function stripe(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');
        $secret = (string) config('services.stripe.webhook_secret');

        try {
            $event = $secret
                ? Webhook::constructEvent($payload, $signature, $secret)
                : json_decode($payload, false, 512, JSON_THROW_ON_ERROR);
        } catch (SignatureVerificationException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid Stripe signature.',
                'data' => null,
            ], 400);
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid Stripe payload.',
                'data' => null,
            ], 400);
        }

        $type = $event->type ?? null;

        if ($type === 'checkout.session.completed') {
            $session = $event->data->object ?? null;
            $sessionId = $session->id ?? null;
            $applicationId = $session->metadata->application_id ?? $session->client_reference_id ?? null;

            if ($sessionId) {
                $payment = Payment::query()
                    ->where('stripe_session_id', $sessionId)
                    ->first();

                if ($payment) {
                    $payment->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);

                    if ($payment->application_id) {
                        Application::where('id', $payment->application_id)
                            ->where('status', '!=', 'paid')
                            ->update(['status' => 'paid']);
                    }
                } elseif ($applicationId) {
                    $payment = Payment::query()
                        ->where('application_id', $applicationId)
                        ->where('status', 'pending')
                        ->first();

                    if ($payment) {
                        $payment->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                            'stripe_session_id' => $sessionId,
                        ]);

                        Application::where('id', $applicationId)
                            ->where('status', '!=', 'paid')
                            ->update(['status' => 'paid']);
                    }
                }
            }
        }

        if ($type === 'checkout.session.expired') {
            $session = $event->data->object ?? null;
            $sessionId = $session->id ?? null;

            if ($sessionId) {
                $payment = Payment::query()
                    ->where('stripe_session_id', $sessionId)
                    ->where('status', 'pending')
                    ->first();

                if ($payment) {
                    $payment->update(['status' => 'failed']);
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Stripe webhook handled.',
            'data' => null,
        ]);
    }
}
