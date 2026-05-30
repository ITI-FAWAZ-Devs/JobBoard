<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
        $intent = $event->data->object ?? null;
        $intentId = $intent->id ?? null;

        if ($intentId) {
            $payment = Payment::query()
                ->where('stripe_payment_intent_id', $intentId)
                ->first();

            if ($payment) {
                if ($type === 'payment_intent.succeeded') {
                    $payment->update(['status' => 'paid']);
                }

                if ($type === 'payment_intent.payment_failed') {
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

    public function paypal(Request $request): JsonResponse
    {
        $payload = $request->all();
        $baseUrl = rtrim((string) config('services.paypal.base_url', 'https://api-m.sandbox.paypal.com'), '/');
        $webhookId = (string) config('services.paypal.webhook_id');

        if ($webhookId) {
            $verified = $this->verifyPayPalSignature($request, $baseUrl, $webhookId);

            if (! $verified) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid PayPal signature.',
                    'data' => null,
                ], 400);
            }
        }

        $eventType = $payload['event_type'] ?? '';
        $resource = $payload['resource'] ?? [];

        $orderId = $resource['id']
            ?? $resource['supplementary_data']['related_ids']['order_id']
            ?? null;

        if ($orderId) {
            $payment = Payment::query()
                ->where('paypal_order_id', $orderId)
                ->first();

            if ($payment) {
                if (in_array($eventType, ['CHECKOUT.ORDER.APPROVED', 'CHECKOUT.ORDER.COMPLETED', 'PAYMENT.CAPTURE.COMPLETED'], true)) {
                    $payment->update(['status' => 'paid']);
                }

                if (in_array($eventType, ['CHECKOUT.ORDER.CANCELLED', 'PAYMENT.CAPTURE.DENIED'], true)) {
                    $payment->update(['status' => 'failed']);
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'PayPal webhook handled.',
            'data' => null,
        ]);
    }

    private function verifyPayPalSignature(Request $request, string $baseUrl, string $webhookId): bool
    {
        $token = $this->getPayPalAccessToken($baseUrl);

        if (! $token) {
            return false;
        }

        $verification = Http::withToken($token)
            ->post($baseUrl.'/v1/notifications/verify-webhook-signature', [
                'transmission_id' => $request->header('paypal-transmission-id'),
                'transmission_time' => $request->header('paypal-transmission-time'),
                'cert_url' => $request->header('paypal-cert-url'),
                'auth_algo' => $request->header('paypal-auth-algo'),
                'transmission_sig' => $request->header('paypal-transmission-sig'),
                'webhook_id' => $webhookId,
                'webhook_event' => $request->all(),
            ]);

        return $verification->json('verification_status') === 'SUCCESS';
    }

    private function getPayPalAccessToken(string $baseUrl): ?string
    {
        $clientId = config('services.paypal.client_id');
        $clientSecret = config('services.paypal.client_secret');

        if (! $clientId || ! $clientSecret) {
            return null;
        }

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
}
