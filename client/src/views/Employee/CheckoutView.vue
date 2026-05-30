<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useMutation, useQuery } from "@tanstack/vue-query";
import { loadStripe, type Stripe, type StripeCardElement, type StripeElements } from "@stripe/stripe-js";
import { ShieldCheck } from "lucide-vue-next";
import EmployerLayout from "@/components/employer/EmployerLayout.vue";
import { Button } from "@/components/ui/button";
import {
  createStripeIntentApi,
  getCandidateContactApi,
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
  return payload?.data ?? payload?.data?.data ?? [];
});
const candidates = computed<CandidateSummary[]>(() => candidatesQuery.data?.data?.data ?? []);

const selectedCandidate = computed(() =>
  candidates.value.find((candidate) => candidate.id === selectedCandidateId.value),
);

const selectedJob = computed(() =>
  jobs.value.find((job) => job.id === selectedJobId.value),
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
    getCandidateContactApi(selectedCandidateId.value as number, selectedJobId.value as number),
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

const resetStripeState = () => {
  stripeClientSecret.value = null;
  stripeError.value = "";
  if (stripeCardElement) {
    stripeCardElement.unmount();
    stripeCardElement = null;
  }
};

const initStripeElements = async () => {
  stripeError.value = "";
  const publishableKey = import.meta.env.VITE_STRIPE_PUBLISHABLE_KEY as string | undefined;
  if (!publishableKey) {
    stripeError.value = "Stripe publishable key is missing.";
    return;
  }

  stripeInstance = stripeInstance ?? (await loadStripe(publishableKey));
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

};

const confirmStripePayment = async () => {
  if (!stripeInstance || !stripeCardElement || !stripeClientSecret.value) {
    stripeError.value = "Stripe is not ready yet.";
    return;
  }

  stripeProcessing.value = true;
  stripeError.value = "";

  const { error, paymentIntent } = await stripeInstance.confirmCardPayment(
    stripeClientSecret.value,
    {
      payment_method: {
        card: stripeCardElement,
      },
    },
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
};

const startStripePayment = async () => {
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
};
const handlePay = async () => {
  await startStripePayment();
};

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
  <EmployerLayout
    title="Checkout"
    subtitle="Select a candidate, choose a payment method, and unlock contact details."
  >
    <template #actions>
      <Button size="sm">Post a Job</Button>
    </template>

    <div class="grid gap-lg lg:grid-cols-[minmax(0,1fr)_320px]">
      <div class="grid gap-lg">
        <section class="rounded-2xl border border-outline-variant bg-card shadow-soft">
          <header class="border-b border-outline-variant px-lg py-md">
            <h2 class="text-lg font-semibold text-on-surface">Select Candidate</h2>
            <p class="text-sm text-on-surface-variant">Pick who you want to unlock.</p>
          </header>
          <div class="divide-y divide-outline-variant">
            <div
              v-for="candidate in candidates"
              :key="candidate.id"
              class="flex flex-wrap items-center justify-between gap-md px-lg py-md"
            >
              <div>
                <p class="text-base font-semibold text-on-surface">{{ candidate.name }}</p>
                <p class="text-sm text-on-surface-variant">
                  {{ candidate.profile?.location || 'Remote' }}
                  <span v-if="candidate.profile?.experience_years"> - {{ candidate.profile?.experience_years }} yrs</span>
                </p>
                <div class="mt-xs flex flex-wrap gap-xs">
                  <span
                    v-for="skill in candidate.profile?.skills?.slice(0, 3) || []"
                    :key="skill"
                    class="rounded-full bg-surface-container-lowest px-sm py-[2px] text-xs text-on-surface-variant"
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
            <div v-if="!candidates.length" class="px-lg py-lg text-sm text-on-surface-variant">
              No candidates available.
            </div>
          </div>
        </section>

        <section class="rounded-2xl border border-outline-variant bg-card shadow-soft">
          <header class="border-b border-outline-variant px-lg py-md">
            <h2 class="text-lg font-semibold text-on-surface">Select Job</h2>
            <p class="text-sm text-on-surface-variant">Attach the payment to a job listing.</p>
          </header>
          <div class="px-lg py-md">
            <select
              v-model.number="selectedJobId"
              class="h-10 w-full rounded-md border border-outline-variant bg-surface-container-lowest px-sm text-sm focus:border-primary focus:outline-none"
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
        <section class="rounded-2xl border border-outline-variant bg-card p-lg shadow-soft">
          <h2 class="text-lg font-semibold text-on-surface">Checkout Summary</h2>
          <div class="mt-md space-y-sm text-sm">
            <div class="flex items-center justify-between">
              <span class="text-on-surface-variant">Candidate</span>
              <span class="font-medium text-on-surface">
                {{ selectedCandidate?.name || 'Not selected' }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-on-surface-variant">Job</span>
              <span class="font-medium text-on-surface">
                {{ selectedJob?.title || 'Not selected' }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-on-surface-variant">Price</span>
              <span class="font-semibold text-on-surface">$49.00</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-on-surface-variant">Provider</span>
              <span class="font-medium text-on-surface">Stripe</span>
            </div>
          </div>

          <Button
            class="mt-md w-full"
            :disabled="!canPay || stripeMutation.isPending || stripeProcessing"
            @click="handlePay"
          >
            <ShieldCheck class="h-4 w-4" aria-hidden="true" />
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

          <p v-if="paymentMessage" class="mt-sm text-xs text-on-surface-variant">
            {{ paymentMessage }}
          </p>
        </section>

        <section class="rounded-2xl border border-outline-variant bg-card p-lg shadow-soft">
          <h2 class="text-lg font-semibold text-on-surface">Payment Details</h2>

          <div class="mt-md space-y-sm">
            <div
              id="stripe-card-element"
              class="rounded-lg border border-outline-variant bg-surface-container-lowest px-sm py-sm"
            ></div>
            <p class="text-xs text-on-surface-variant">
              Enter card details to complete the Stripe payment.
            </p>
            <p v-if="stripeError" class="text-xs text-destructive">
              {{ stripeError }}
            </p>
          </div>
        </section>

        <section class="rounded-2xl border border-outline-variant bg-card p-lg shadow-soft">
          <h2 class="text-lg font-semibold text-on-surface">Contact Details</h2>
          <div v-if="contactDetails" class="mt-md space-y-xs text-sm">
            <div class="flex items-center justify-between">
              <span class="text-on-surface-variant">Email</span>
              <span class="font-medium text-on-surface">{{ contactDetails.email }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-on-surface-variant">Phone</span>
              <span class="font-medium text-on-surface">{{ contactDetails.phone || 'N/A' }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-on-surface-variant">LinkedIn</span>
              <span class="font-medium text-on-surface">
                {{ contactDetails.linkedin_url || 'N/A' }}
              </span>
            </div>
          </div>
          <div v-else class="mt-md text-sm text-on-surface-variant">
            Contact details will appear here after payment confirmation.
          </div>
        </section>
      </div>
    </div>
  </EmployerLayout>
</template>
