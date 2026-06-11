<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { CheckCircle2, XCircle, Clock, AlertTriangle, Search } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { useAdminJobsStore } from "@/stores/adminJobs";

const store = useAdminJobsStore();
const page = ref(1);
const rejectModal = ref(false);
const rejectJobId = ref<number | null>(null);
const rejectReason = ref("");

onMounted(() => store.fetchJobs());

const jobs = computed(() => store.jobs);
const totalPending = computed(() => store.totalPending);

const formatDate = (value?: string | null) => {
  if (!value) return "";
  return new Date(value).toLocaleDateString();
};

function openRejectModal(jobId: number) {
  rejectJobId.value = jobId;
  rejectReason.value = "";
  rejectModal.value = true;
}

function closeRejectModal() {
  rejectModal.value = false;
  rejectJobId.value = null;
  rejectReason.value = "";
}

async function confirmReject() {
  if (!rejectJobId.value || !rejectReason.value.trim()) return;
  await store.rejectJob(rejectJobId.value, rejectReason.value.trim());
  closeRejectModal();
}

async function handleApprove(jobId: number) {
  await store.approveJob(jobId);
}
</script>

<template>
  <div class="min-h-screen bg-background text-on-background">
    <main class="mx-auto w-full max-w-container-max flex-1 p-md md:p-lg">
      <div class="mb-xl flex flex-col justify-between gap-sm md:flex-row md:items-center">
        <div>
          <h1 class="font-headline-lg text-headline-lg text-on-background">Pending Job Approvals</h1>
          <p class="mt-1 font-body-md text-body-md text-on-surface-variant">
            Review and moderate new job listings.
          </p>
        </div>
        <div class="font-label-md text-label-md text-on-surface-variant">
          {{ totalPending }} pending
        </div>
      </div>

      <div class="mb-xl grid grid-cols-1 gap-md md:grid-cols-3">
        <article class="flex flex-col justify-between rounded-xl bg-surface-container-lowest p-md shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
          <div class="mb-sm flex items-start justify-between">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-surface-container text-primary">
              <Clock class="h-5 w-5" aria-hidden="true" />
            </div>
          </div>
          <div>
            <p class="mb-1 font-label-md text-label-md text-on-surface-variant">Pending Approvals</p>
            <h3 class="font-headline-xl text-headline-xl text-on-background">{{ totalPending }}</h3>
          </div>
        </article>
      </div>

      <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
        <div class="border-b border-outline-variant p-md">
          <h2 class="font-headline-md text-headline-md text-on-background">Pending Job Queue</h2>
        </div>

        <div v-if="store.loading" class="divide-y divide-outline-variant">
          <div v-for="i in 4" :key="i" class="flex items-center gap-md p-md animate-pulse">
            <div class="h-5 w-48 rounded bg-surface-container-high"></div>
            <div class="h-5 w-32 rounded bg-surface-container-high"></div>
            <div class="h-5 w-24 rounded bg-surface-container-high"></div>
            <div class="h-5 w-20 rounded bg-surface-container-high"></div>
            <div class="ml-auto flex gap-sm">
              <div class="h-8 w-20 rounded bg-surface-container-high"></div>
              <div class="h-8 w-20 rounded bg-surface-container-high"></div>
            </div>
          </div>
        </div>

        <div v-else-if="store.error" class="flex items-center gap-sm p-md font-body-sm text-body-sm text-destructive">
          <AlertTriangle class="h-4 w-4" />
          {{ store.error }}
        </div>

        <div v-else-if="!jobs.length" class="flex flex-col items-center justify-center p-xl text-center">
          <CheckCircle2 class="mb-sm h-12 w-12 text-secondary" />
          <p class="font-label-md text-label-md text-on-background">All caught up!</p>
          <p class="font-body-sm text-body-sm text-on-surface-variant">No pending jobs require moderation.</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full border-collapse text-left">
            <thead>
              <tr class="border-b border-outline-variant bg-surface">
                <th class="p-md font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Job Title</th>
                <th class="p-md font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Employer</th>
                <th class="p-md font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Category</th>
                <th class="p-md font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Posted At</th>
                <th class="p-md text-right font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
              <tr v-for="job in jobs" :key="job.id" class="group transition-colors hover:bg-surface-container-low">
                <td class="p-md font-label-md text-label-md text-on-background">{{ job.title }}</td>
                <td class="p-md font-body-sm text-body-sm text-on-surface-variant">{{ job.employer_profile?.company_name || '—' }}</td>
                <td class="p-md font-body-sm text-body-sm text-on-surface-variant">{{ job.category?.name || '—' }}</td>
                <td class="p-md font-body-sm text-body-sm text-on-surface-variant">{{ formatDate(job.created_at) }}</td>
                <td class="p-md text-right">
                  <div class="flex items-center justify-end gap-xs">
                    <Button
                      size="sm"
                      variant="outline"
                      class="border-secondary text-secondary hover:bg-secondary/10"
                      :disabled="store.actionLoading[job.id]"
                      @click="handleApprove(job.id)"
                    >
                      <CheckCircle2 class="h-4 w-4" aria-hidden="true" />
                      Approve
                    </Button>
                    <Button
                      size="sm"
                      variant="outline"
                      class="border-destructive text-destructive hover:bg-destructive/10"
                      :disabled="store.actionLoading[job.id]"
                      @click="openRejectModal(job.id)"
                    >
                      <XCircle class="h-4 w-4" aria-hidden="true" />
                      Reject
                    </Button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>

    <Teleport to="body">
      <div
        v-if="rejectModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="closeRejectModal"
      >
        <div class="w-full max-w-md rounded-xl bg-surface-container-lowest p-lg shadow-lg">
          <h3 class="font-headline-md text-headline-md text-on-background">Reject Job</h3>
          <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">Provide a reason for the rejection.</p>

          <textarea
            v-model="rejectReason"
            class="mt-md min-h-[100px] w-full rounded-lg border border-outline-variant bg-surface-container-lowest p-sm font-body-sm text-body-sm placeholder:text-on-surface-variant focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            placeholder="e.g. Incomplete job description, inappropriate content..."
          ></textarea>

          <div class="mt-md flex items-center justify-end gap-sm">
            <Button variant="outline" size="sm" @click="closeRejectModal">Cancel</Button>
            <Button
              size="sm"
              variant="destructive"
              :disabled="!rejectReason.trim() || store.actionLoading[rejectJobId ?? 0]"
              @click="confirmReject"
            >
              Confirm Reject
            </Button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
