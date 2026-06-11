<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { ArrowLeft, Lock, ShieldCheck } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { toast } from "vue-sonner";
import Navbar from "@/components/shared/Navbar.vue";
import Footer from "@/components/shared/Footer.vue";
import {
  getApplicationCheckoutApi,
  createStripeCheckoutSessionApi,
} from "@/api/employer";

const route = useRoute();
const router = useRouter();
const applicationId = Number(route.params.applicationId);

const loading = ref(true);
const processing = ref(false);
const checkoutData = ref<{
  candidate_name: string;
  job_title: string;
  amount: number;
  currency: string;
} | null>(null);

onMounted(async () => {
  try {
    const res = await getApplicationCheckoutApi(applicationId);
    checkoutData.value = res.data;
  } catch {
    toast.error("Failed to load checkout details");
  } finally {
    loading.value = false;
  }
});

async function handleStripeCheckout() {
  processing.value = true;
  try {
    const res = await createStripeCheckoutSessionApi(applicationId);
    window.location.href = res.data.session_url;
  } catch (e: any) {
    toast.error(e?.response?.data?.message || "Failed to create checkout session");
  } finally {
    processing.value = false;
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

        <!-- Payment Section -->
        <section class="rounded-2xl border border-outline-variant bg-card p-lg shadow-soft">
          <div class="flex items-center gap-2 mb-4">
            <Lock class="h-5 w-5 text-primary" />
            <h2 class="text-lg font-semibold text-on-surface">Pay with Stripe</h2>
          </div>
          <p class="text-sm leading-relaxed text-on-surface-variant mb-6">
            You will be redirected to Stripe's secure checkout page to complete your payment.
          </p>

          <Button
            class="w-full gap-2"
            :disabled="processing"
            @click="handleStripeCheckout"
          >
            <ShieldCheck class="h-4 w-4" />
            {{ processing ? 'Redirecting to Stripe...' : `Pay ${checkoutData.currency} ${checkoutData.amount.toFixed(2)}` }}
          </Button>
        </section>
      </template>

      <div v-else class="rounded-2xl border border-outline-variant bg-card p-lg text-center text-sm leading-relaxed text-on-surface-variant">
        Could not load checkout details.
      </div>
    </main>
    <Footer />
  </div>
</template>
