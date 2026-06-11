<script setup lang="ts">
import { computed, ref } from "vue";
import { useRouter } from "vue-router";
import { ShieldCheck, ExternalLink } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { toast } from "vue-sonner";
import {
  getCandidatesApi,
  getEmployerJobsApi,
  createStripeCheckoutSessionApi,
  type CandidateSummary,
  type JobListing,
} from "@/api/employer";
import { useQuery } from "@tanstack/vue-query";

const router = useRouter();
const selectedCandidateId = ref<number | null>(null);
const selectedJobId = ref<number | null>(null);
const processing = ref(false);

const jobsQuery = useQuery({
  queryKey: ["employer", "jobs"],
  queryFn: () => getEmployerJobsApi(1),
});

const candidatesQuery = useQuery({
  queryKey: ["employer", "candidates"],
  queryFn: () => getCandidatesApi(1),
});

const jobs = computed<JobListing[]>(() => {
  const payload = jobsQuery.data.value;
  if (Array.isArray(payload)) return payload as JobListing[];
  return payload?.data?.data ?? [];
});

const candidates = computed<CandidateSummary[]>(() => candidatesQuery.data.value?.data?.data ?? []);

const selectedCandidate = computed(() =>
  candidates.value.find((c) => c.id === selectedCandidateId.value),
);

const selectedJob = computed(() =>
  jobs.value.find((j) => j.id === selectedJobId.value),
);

const canPay = computed(() => Boolean(selectedCandidateId.value && selectedJobId.value));

async function handlePay() {
  if (!canPay.value) return;
  processing.value = true;
  try {
    toast.info("This feature requires going through the application flow.");
    router.push("/employer/applications");
  } catch {
    toast.error("Failed to process payment");
  } finally {
    processing.value = false;
  }
}
</script>

<template>
  <div class="min-h-screen bg-background text-on-background">
    <main class="mx-auto w-full max-w-container-max flex-1 p-md md:p-lg">
      <div class="mb-xl flex flex-col justify-between gap-sm md:flex-row md:items-center">
        <div>
          <h1 class="font-headline-lg text-headline-lg text-on-background">Payment Checkout</h1>
          <p class="mt-1 font-body-md text-body-md leading-relaxed text-on-surface-variant">
            Unlock candidate contact details through the application flow.
          </p>
        </div>
      </div>

      <div class="grid gap-lg lg:grid-cols-[minmax(0,1fr)_320px]">
        <div class="grid gap-lg">
          <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
            <div class="border-b border-outline-variant p-md">
              <h2 class="font-headline-md text-headline-md text-on-background">Select Candidate</h2>
              <p class="font-body-sm text-body-sm leading-relaxed text-on-surface-variant">Pick who you want to unlock.</p>
            </div>
            <div class="divide-y divide-outline-variant">
              <div
                v-for="candidate in candidates"
                :key="candidate.id"
                class="flex flex-wrap items-center justify-between gap-md p-md"
              >
                <div>
                  <p class="font-label-md text-label-md text-on-background">{{ candidate.name }}</p>
                  <p class="font-body-sm text-body-sm leading-relaxed text-on-surface-variant">
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
              <p class="font-body-sm text-body-sm leading-relaxed text-on-surface-variant">Attach the payment to a job listing.</p>
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
            </div>

            <div class="border-t border-outline-variant p-md">
              <Button
                class="w-full gap-2"
                :disabled="!canPay || processing"
                @click="handlePay"
              >
                <ExternalLink class="h-4 w-4" />
                Go to Application Inbox
              </Button>

              <p class="mt-sm text-center text-xs leading-relaxed text-on-surface-variant">
                Payment unlocking is now handled through the application inbox.
              </p>
            </div>
          </section>
        </div>
      </div>
    </main>
  </div>
</template>
