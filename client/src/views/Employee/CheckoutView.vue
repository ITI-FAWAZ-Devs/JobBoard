<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useMutation, useQuery } from "@tanstack/vue-query";
import { loadStripe, type Stripe, type StripeCardElement, type StripeElements } from "@stripe/stripe-js";
import { ShieldCheck, CreditCard } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import {
  createStripeIntentApi,
  getCandidateContactApiV1,
  getCandidatesApi,
  getEmployerJobsApi,
  type CandidateSummary,
  type JobListing,
} from "@/api/employer";

const selectedCandidateId = ref<number | null>(null);
const selectedJobId = ref<number | null>(null);

const paymentMessage = ref("");
const stripeClientSecret = ref<string | null>(null);
const stripeError = ref("");
const stripeProcessing = ref(false);
const contactDetails = ref<{
  email: string;
  phone?: string | null;
  linkedin_url?: string | null;
} | null>(null);

let stripeInstance: Stripe | null = null;
let stripeElements: StripeElements | null = null;
let stripeCardElement: StripeCardElement | null = null;

const jobsQuery = useQuery({
  queryKey: ["employer", "jobs"],
  queryFn: () => getEmployerJobsApi(1),
});

const candidatesQuery = useQuery({
  queryKey: ["employer", "candidates"],
  queryFn: () => getCandidatesApi(1),
});

const jobs = computed<JobListing[]>(() => {
  const payload = jobsQuery.data;
  if (Array.isArray(payload)) return payload as JobListing[];
  return payload?.data?.data ?? [];
});

const candidates = computed<CandidateSummary[]>(() => candidatesQuery.data?.data?.data ?? []);

const selectedCandidate = computed(() =>
  candidates.value.find((c) => c.id === selectedCandidateId.value),
);

const selectedJob = computed(() =>
  jobs.value.find((j) => j.id === selectedJobId.value),
);

const stripeMutation = useMutation({
  mutationFn: () =>
    createStripeIntentApi({
      job_id: selectedJobId.value as number,
      candidate_id: selectedCandidateId.value as number,
    }),
});

const contactMutation = useMutation({
  mutationFn: () =>
    getCandidateContactApiV1(selectedCandidateId.value as number, selectedJobId.value as number),
  onSuccess: (res) => {
    contactDetails.value = {
      email: res.data.email,
      phone: res.data.phone,
      linkedin_url: res.data.linkedin_url,
    };
    paymentMessage.value = "Contact details unlocked.";
  },
  onError: () => {
    paymentMessage.value = "Payment required to reveal contact details.";
    contactDetails.value = null;
  },
});

const canPay = computed(() => Boolean(selectedCandidateId.value && selectedJobId.value));

function resetStripeState() {
  stripeClientSecret.value = null;
  stripeError.value = "";
  if (stripeCardElement) {
    stripeCardElement.unmount();
    stripeCardElement = null;
  }
}

async function initStripeElements() {
  stripeError.value = "";
  const key = import.meta.env.VITE_STRIPE_PUBLISHABLE_KEY as string | undefined;
  if (!key) {
    stripeError.value = "Stripe publishable key is missing.";
    return;
  }
  stripeInstance = stripeInstance ?? (await loadStripe(key));
  if (!stripeInstance) {
    stripeError.value = "Stripe failed to initialize.";
    return;
  }
  stripeElements = stripeElements ?? stripeInstance.elements();
  if (!stripeCardElement) {
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
}

async function confirmStripePayment() {
  if (!stripeInstance || !stripeCardElement || !stripeClientSecret.value) {
    stripeError.value = "Stripe is not ready yet.";
    return;
  }
  stripeProcessing.value = true;
  stripeError.value = "";
  const { error, paymentIntent } = await stripeInstance.confirmCardPayment(
    stripeClientSecret.value,
    { payment_method: { card: stripeCardElement } },
  );
  stripeProcessing.value = false;
  if (error) {
    stripeError.value = error.message || "Stripe payment failed.";
    paymentMessage.value = stripeError.value;
    return;
  }
  if (paymentIntent?.status === "succeeded") {
    paymentMessage.value = "Stripe payment completed.";
    contactMutation.mutate();
  } else {
    paymentMessage.value = "Stripe payment is processing. Check again shortly.";
  }
}

async function handlePay() {
  if (!canPay.value) return;
  try {
    if (!stripeClientSecret.value) {
      const res = await stripeMutation.mutateAsync();
      stripeClientSecret.value = res.data.client_secret;
      await nextTick();
      await initStripeElements();
    }
    await confirmStripePayment();
  } catch {
    stripeError.value = "Stripe payment failed to start.";
    paymentMessage.value = stripeError.value;
  }
}

watch([selectedCandidateId, selectedJobId], async () => {
  paymentMessage.value = "";
  resetStripeState();
  await nextTick();
  await initStripeElements();
});

onMounted(async () => {
  await initStripeElements();
});

onBeforeUnmount(() => {
  resetStripeState();
});
</script>

<template>
  <div class="min-h-screen bg-background text-on-background">
    <main class="mx-auto w-full max-w-container-max flex-1 p-md md:p-lg">
      <div class="mb-xl flex flex-col justify-between gap-sm md:flex-row md:items-center">
        <div>
          <h1 class="font-headline-lg text-headline-lg text-on-background">Payment Checkout</h1>
          <p class="mt-1 font-body-md text-body-md text-on-surface-variant">
            Unlock candidate contact details.
          </p>
        </div>
      </div>

      <div class="grid gap-lg lg:grid-cols-[minmax(0,1fr)_320px]">
        <div class="grid gap-lg">
          <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
            <div class="border-b border-outline-variant p-md">
              <h2 class="font-headline-md text-headline-md text-on-background">Select Candidate</h2>
              <p class="font-body-sm text-body-sm text-on-surface-variant">Pick who you want to unlock.</p>
            </div>
            <div class="divide-y divide-outline-variant">
              <div
                v-for="candidate in candidates"
                :key="candidate.id"
                class="flex flex-wrap items-center justify-between gap-md p-md"
              >
                <div>
                  <p class="font-label-md text-label-md text-on-background">{{ candidate.name }}</p>
                  <p class="font-body-sm text-body-sm text-on-surface-variant">
                    {{ candidate.profile?.location || 'Remote' }}
                    <span v-if="candidate.profile?.experience_years"> - {{ candidate.profile?.experience_years }} yrs</span>
                  </p>
                  <div class="mt-xs flex flex-wrap gap-xs">
                    <span
                      v-for="skill in candidate.profile?.skills?.slice(0, 3) || []"
                      :key="skill"
                      class="rounded-full bg-surface-container-high px-2 py-0.5 font-label-sm text-label-sm text-on-surface-variant"
                    >
                      {{ skill }}
                    </span>
                  </div>
                </div>
                <Button
                  size="sm"
                  variant="outline"
                  :disabled="selectedCandidateId === candidate.id"
                  @click="selectedCandidateId = candidate.id"
                >
                  {{ selectedCandidateId === candidate.id ? 'Selected' : 'Choose' }}
                </Button>
              </div>
              <div v-if="!candidates.length" class="p-md font-body-sm text-body-sm text-on-surface-variant">
                No candidates available.
              </div>
            </div>
          </section>

          <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
            <div class="border-b border-outline-variant p-md">
              <h2 class="font-headline-md text-headline-md text-on-background">Select Job</h2>
              <p class="font-body-sm text-body-sm text-on-surface-variant">Attach the payment to a job listing.</p>
            </div>
            <div class="p-md">
              <select
                v-model.number="selectedJobId"
                class="w-full rounded-lg border border-outline-variant bg-surface-container-lowest py-2 pl-3 pr-4 font-body-sm text-body-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
              >
                <option :value="null" disabled>Select a job</option>
                <option v-for="job in jobs" :key="job.id" :value="job.id">
                  {{ job.title }}
                </option>
              </select>
            </div>
          </section>
        </div>

        <div class="grid gap-lg">
          <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
            <div class="border-b border-outline-variant p-md">
              <h2 class="font-headline-md text-headline-md text-on-background">Checkout Summary</h2>
            </div>
            <div class="space-y-sm p-md">
              <div class="flex items-center justify-between">
                <span class="font-body-sm text-body-sm text-on-surface-variant">Candidate</span>
                <span class="font-label-md text-label-md text-on-background">
                  {{ selectedCandidate?.name || 'Not selected' }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="font-body-sm text-body-sm text-on-surface-variant">Job</span>
                <span class="font-label-md text-label-md text-on-background">
                  {{ selectedJob?.title || 'Not selected' }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="font-body-sm text-body-sm text-on-surface-variant">Price</span>
                <span class="font-label-md text-label-md text-on-background">$49.00</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="font-body-sm text-body-sm text-on-surface-variant">Provider</span>
                <span class="font-label-md text-label-md text-on-background">Stripe</span>
              </div>
            </div>

            <div class="border-t border-outline-variant p-md">
              <Button
                class="w-full"
                :disabled="!canPay || stripeMutation.isPending || stripeProcessing"
                @click="handlePay"
              >
                <ShieldCheck class="mr-xs h-4 w-4" aria-hidden="true" />
                Pay and Unlock
              </Button>

              <Button
                variant="outline"
                class="mt-sm w-full"
                :disabled="!canPay || contactMutation.isPending"
                @click="contactMutation.mutate()"
              >
                Reveal Contact
              </Button>

              <p v-if="paymentMessage" class="mt-sm font-body-xs text-body-xs text-on-surface-variant">
                {{ paymentMessage }}
              </p>
            </div>
          </section>

          <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
            <div class="border-b border-outline-variant p-md">
              <h2 class="font-headline-md text-headline-md text-on-background">
                <CreditCard class="mr-xs inline h-5 w-5" aria-hidden="true" />
                Payment Details
              </h2>
            </div>
            <div class="space-y-sm p-md">
              <div
                id="stripe-card-element"
                class="rounded-lg border border-outline-variant bg-surface-container-lowest px-sm py-sm"
              ></div>
              <p class="font-body-xs text-body-xs text-on-surface-variant">
                Enter card details to complete the Stripe payment.
              </p>
              <p v-if="stripeError" class="font-body-xs text-body-xs text-destructive">
                {{ stripeError }}
              </p>
            </div>
          </section>

          <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
            <div class="border-b border-outline-variant p-md">
              <h2 class="font-headline-md text-headline-md text-on-background">Contact Details</h2>
            </div>
            <div class="p-md">
              <div v-if="contactDetails" class="space-y-xs">
                <div class="flex items-center justify-between">
                  <span class="font-body-sm text-body-sm text-on-surface-variant">Email</span>
                  <span class="font-label-md text-label-md text-on-background">{{ contactDetails.email }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="font-body-sm text-body-sm text-on-surface-variant">Phone</span>
                  <span class="font-label-md text-label-md text-on-background">{{ contactDetails.phone || 'N/A' }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="font-body-sm text-body-sm text-on-surface-variant">LinkedIn</span>
                  <span class="font-label-md text-label-md text-on-background">{{ contactDetails.linkedin_url || 'N/A' }}</span>
                </div>
              </div>
              <p v-else class="font-body-sm text-body-sm text-on-surface-variant">
                Contact details will appear here after payment confirmation.
              </p>
            </div>
          </section>
        </div>
      </div>
    </main>
  </div>
</template>
