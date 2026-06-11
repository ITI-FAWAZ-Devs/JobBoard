<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { loadStripe, type Stripe, type StripeCardElement, type StripeElements } from "@stripe/stripe-js";
import { ArrowLeft, CreditCard, ShieldCheck } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { toast } from "vue-sonner";
import Navbar from "@/components/shared/Navbar.vue";
import Footer from "@/components/shared/Footer.vue";
import {
  getApplicationCheckoutApi,
  createStripePaymentIntentApi,
  createPayPalOrderApi,
} from "@/api/employer";

const route = useRoute();
const router = useRouter();
const applicationId = Number(route.params.applicationId);

const activeTab = ref<"stripe" | "paypal">("stripe");
const loading = ref(true);
const checkoutData = ref<{
  candidate_name: string;
  job_title: string;
  amount: number;
  currency: string;
} | null>(null);

const stripeProcessing = ref(false);
const stripeError = ref("");
let stripeInstance: Stripe | null = null;
let stripeElements: StripeElements | null = null;
let stripeCardElement: StripeCardElement | null = null;

onMounted(async () => {
  try {
    const res = await getApplicationCheckoutApi(applicationId);
    checkoutData.value = res.data;
    await initStripeElements();
  } catch {
    toast.error("Failed to load checkout details");
  } finally {
    loading.value = false;
  }
});

onBeforeUnmount(() => {
  resetStripe();
});

function resetStripe() {
  if (stripeCardElement) {
    stripeCardElement.unmount();
    stripeCardElement = null;
  }
  stripeElements = null;
  stripeInstance = null;
}

async function initStripeElements() {
  const key = import.meta.env.VITE_STRIPE_PUBLISHABLE_KEY as string;
  if (!key) {
    stripeError.value = "Stripe key not configured";
    return;
  }
  stripeInstance = await loadStripe(key);
  if (!stripeInstance) {
    stripeError.value = "Failed to load Stripe";
    return;
  }
  stripeElements = stripeInstance.elements();
  stripeCardElement = stripeElements.create("card", {
    style: {
      base: {
        color: "#0b1c30",
        fontFamily: "Inter, system-ui, sans-serif",
        fontSize: "14px",
      },
    },
  });
  await nextTick();
  stripeCardElement.mount("#stripe-card-element");
}

async function handleStripePayment() {
  if (!stripeInstance || !stripeCardElement) return;
  stripeProcessing.value = true;
  stripeError.value = "";
  try {
    const res = await createStripePaymentIntentApi(applicationId);
    const clientSecret = res.data.client_secret;
    const { error, paymentIntent } = await stripeInstance.confirmCardPayment(clientSecret, {
      payment_method: { card: stripeCardElement },
    });
    if (error) {
      stripeError.value = error.message || "Payment failed";
      toast.error(stripeError.value);
      return;
    }
    if (paymentIntent?.status === "succeeded") {
      toast.success("Payment successful!");
      router.push(`/payment/success/${applicationId}`);
    }
  } catch (e: any) {
    stripeError.value = e?.response?.data?.message || "Payment failed";
    toast.error(stripeError.value);
  } finally {
    stripeProcessing.value = false;
  }
}

async function handlePayPalPayment() {
  try {
    const res = await createPayPalOrderApi(applicationId);
    window.location.href = res.data.approve_url;
  } catch {
    toast.error("Failed to create PayPal order");
  }
}
</script>

<template>
  <div class="min-h-screen bg-surface text-on-surface antialiased">
    <Navbar />
    <main class="mx-auto max-w-3xl px-lg py-xl">
      <button
        class="mb-md flex items-center gap-xs text-sm text-on-surface-variant hover:text-on-surface transition-colors"
        @click="router.back()"
      >
        <ArrowLeft class="h-4 w-4" />
        Back
      </button>

      <div v-if="loading" class="space-y-md animate-pulse">
        <div class="h-8 w-64 rounded bg-surface-container-low"></div>
        <div class="h-40 rounded-2xl bg-surface-container-low"></div>
      </div>

      <template v-else-if="checkoutData">
        <h1 class="mb-lg text-2xl font-semibold text-on-surface">Payment Checkout</h1>

        <!-- Summary Card -->
        <section class="mb-lg rounded-2xl border border-outline-variant bg-card p-lg shadow-soft">
          <h2 class="mb-md text-lg font-semibold text-on-surface">Order Summary</h2>
          <div class="space-y-sm text-sm">
            <div class="flex items-center justify-between">
              <span class="text-on-surface-variant">Candidate</span>
              <span class="font-medium text-on-surface">{{ checkoutData.candidate_name }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-on-surface-variant">Job</span>
              <span class="font-medium text-on-surface">{{ checkoutData.job_title }}</span>
            </div>
            <div class="border-t border-outline-variant pt-sm">
              <div class="flex items-center justify-between">
                <span class="text-base font-semibold text-on-surface">Total</span>
                <span class="text-xl font-bold text-primary">
                  {{ checkoutData.currency }} {{ checkoutData.amount.toFixed(2) }}
                </span>
              </div>
            </div>
          </div>
        </section>

        <!-- Payment Method Tabs -->
        <section class="rounded-2xl border border-outline-variant bg-card shadow-soft">
          <div class="flex border-b border-outline-variant">
            <button
              class="flex-1 px-lg py-sm text-sm font-medium transition"
              :class="activeTab === 'stripe'
                ? 'border-b-2 border-primary text-primary'
                : 'text-on-surface-variant hover:text-on-surface'"
              @click="activeTab = 'stripe'"
            >
              <CreditCard class="mr-xs inline h-4 w-4" />
              Stripe
            </button>
            <button
              class="flex-1 px-lg py-sm text-sm font-medium transition"
              :class="activeTab === 'paypal'
                ? 'border-b-2 border-primary text-primary'
                : 'text-on-surface-variant hover:text-on-surface'"
              @click="activeTab = 'paypal'"
            >
              PayPal
            </button>
          </div>

          <div class="p-lg">
            <!-- Stripe -->
            <div v-if="activeTab === 'stripe'" class="space-y-md">
              <div
                id="stripe-card-element"
                class="rounded-lg border border-outline-variant bg-surface-container-lowest px-sm py-sm"
              ></div>
              <p v-if="stripeError" class="text-xs text-destructive">{{ stripeError }}</p>
              <Button
                class="w-full"
                :disabled="stripeProcessing"
                @click="handleStripePayment"
              >
                <ShieldCheck class="mr-xs h-4 w-4" />
                {{ stripeProcessing ? 'Processing...' : `Pay ${checkoutData.currency} ${checkoutData.amount.toFixed(2)}` }}
              </Button>
            </div>

            <!-- PayPal -->
            <div v-if="activeTab === 'paypal'" class="space-y-md">
              <p class="text-sm text-on-surface-variant">
                You will be redirected to PayPal to complete the payment.
              </p>
              <Button class="w-full" @click="handlePayPalPayment">
                Pay with PayPal
              </Button>
            </div>
          </div>
        </section>
      </template>

      <div v-else class="rounded-2xl border border-outline-variant bg-card p-lg text-center text-sm text-on-surface-variant">
        Could not load checkout details.
      </div>
    </main>
    <Footer />
  </div>
</template>
