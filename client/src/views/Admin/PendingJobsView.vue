<script setup lang="ts">
import { computed, ref } from "vue";
import { useMutation, useQuery, useQueryClient } from "@tanstack/vue-query";
import { CheckCircle2, Clock3, XCircle } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import {
  approveJobApi,
  getPendingJobsApi,
  rejectJobApi,
  type JobListing,
} from "@/api/admin";

const queryClient = useQueryClient();
const page = ref(1);
const activeRejectId = ref<number | null>(null);
const rejectionReason = ref<Record<number, string>>({});

const { data, isPending, isError } = useQuery({
  queryKey: ["admin", "pending-jobs", page],
  queryFn: () => getPendingJobsApi(page.value),
  keepPreviousData: true,
});

const jobs = computed<JobListing[]>(() => data.value?.data?.data ?? []);
const meta = computed(() => data.value?.data?.meta);

const approveMutation = useMutation({
  mutationFn: (jobId: number) => approveJobApi(jobId),
  onSuccess: () => queryClient.invalidateQueries({ queryKey: ["admin", "pending-jobs"] }),
});

const rejectMutation = useMutation({
  mutationFn: ({ jobId, reason }: { jobId: number; reason: string }) =>
    rejectJobApi(jobId, reason),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ["admin", "pending-jobs"] });
    if (activeRejectId.value) {
      rejectionReason.value[activeRejectId.value] = "";
    }
    activeRejectId.value = null;
  },
});

const totalPending = computed(() => meta.value?.total ?? jobs.value.length);

const formatDate = (value?: string | null) => {
  if (!value) return "";
  const date = new Date(value);
  return date.toLocaleDateString();
};

const openReject = (jobId: number) => {
  activeRejectId.value = jobId;
};

const submitReject = (jobId: number) => {
  const reason = rejectionReason.value[jobId]?.trim();
  if (!reason) return;
  rejectMutation.mutate({ jobId, reason });
};
</script>

<template>
  <div>
    <div class="mb-md flex flex-wrap items-center justify-end gap-sm">
      <Button variant="outline" size="sm">Export Data</Button>
      <Button size="sm">New Report</Button>
    </div>

    <div class="grid gap-lg">
      <div class="grid gap-md md:grid-cols-3">
        <div class="rounded-xl border border-outline-variant bg-card p-md shadow-soft">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-on-surface-variant">Pending Approvals</p>
              <p class="text-3xl font-semibold text-on-surface">{{ totalPending }}</p>
            </div>
            <div class="rounded-full bg-accent p-2 text-primary">
              <Clock3 class="h-5 w-5" aria-hidden="true" />
            </div>
          </div>
          <p class="mt-sm text-xs text-on-surface-variant">
            Updated just now
          </p>
        </div>
        <div class="rounded-xl border border-outline-variant bg-card p-md shadow-soft">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-on-surface-variant">Approved Today</p>
              <p class="text-3xl font-semibold text-on-surface">--</p>
            </div>
            <div class="rounded-full bg-secondary/10 p-2 text-secondary">
              <CheckCircle2 class="h-5 w-5" aria-hidden="true" />
            </div>
          </div>
          <p class="mt-sm text-xs text-on-surface-variant">
            Auto-calculated next sprint
          </p>
        </div>
        <div class="rounded-xl border border-outline-variant bg-card p-md shadow-soft">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-on-surface-variant">Rejected Today</p>
              <p class="text-3xl font-semibold text-on-surface">--</p>
            </div>
            <div class="rounded-full bg-destructive/10 p-2 text-destructive">
              <XCircle class="h-5 w-5" aria-hidden="true" />
            </div>
          </div>
          <p class="mt-sm text-xs text-on-surface-variant">
            Keep approvals moving
          </p>
        </div>
      </div>

      <section class="rounded-2xl border border-outline-variant bg-card shadow-soft">
        <header class="flex items-center justify-between border-b border-outline-variant px-lg py-md">
          <div>
            <h2 class="text-lg font-semibold text-on-surface">Pending Job Queue</h2>
            <p class="text-sm text-on-surface-variant">Approve or reject new listings.</p>
          </div>
          <div class="text-sm text-on-surface-variant">
            {{ totalPending }} items
          </div>
        </header>

        <div v-if="isPending" class="px-lg py-lg text-sm text-on-surface-variant">
          Loading pending jobs...
        </div>
        <div v-else-if="isError" class="px-lg py-lg text-sm text-destructive">
          Failed to load pending jobs. Please refresh.
        </div>
        <div v-else-if="!jobs.length" class="px-lg py-lg text-sm text-on-surface-variant">
          No pending jobs right now.
        </div>
        <div v-else class="divide-y divide-outline-variant">
          <div v-for="job in jobs" :key="job.id" class="px-lg py-md">
            <div class="flex flex-wrap items-center justify-between gap-md">
              <div class="min-w-[240px]">
                <p class="text-base font-semibold text-on-surface">{{ job.title }}</p>
                <p class="text-sm text-on-surface-variant">
                  {{ job.employer_profile?.company_name || 'Unknown company' }}
                  <span v-if="job.location"> - {{ job.location }}</span>
                </p>
                <p class="text-xs text-on-surface-variant">
                  Submitted {{ formatDate(job.created_at) }}
                </p>
              </div>

              <div class="flex flex-wrap items-center gap-sm">
                <Button
                  variant="outline"
                  size="sm"
                  :disabled="approveMutation.isPending"
                  @click="approveMutation.mutate(job.id)"
                >
                  Approve
                </Button>
                <Button
                  variant="destructive"
                  size="sm"
                  :disabled="rejectMutation.isPending"
                  @click="openReject(job.id)"
                >
                  Reject
                </Button>
              </div>
            </div>

            <div v-if="activeRejectId === job.id" class="mt-md rounded-lg border border-outline-variant bg-surface-container-lowest p-md">
              <label class="mb-xs block text-xs font-medium text-on-surface-variant">
                Rejection reason
              </label>
              <div class="flex flex-wrap items-center gap-sm">
                <input
                  v-model="rejectionReason[job.id]"
                  class="h-9 flex-1 rounded-md border border-outline-variant bg-transparent px-sm text-sm focus:border-primary focus:outline-none"
                  placeholder="Provide a short reason"
                  type="text"
                />
                <Button
                  variant="outline"
                  size="sm"
                  type="button"
                  @click="activeRejectId = null"
                >
                  Cancel
                </Button>
                <Button
                  size="sm"
                  type="button"
                  :disabled="rejectMutation.isPending || !rejectionReason[job.id]"
                  @click="submitReject(job.id)"
                >
                  Confirm
                </Button>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>
