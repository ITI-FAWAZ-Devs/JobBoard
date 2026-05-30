<script setup lang="ts">
import { computed, ref } from "vue";
import { useMutation, useQuery, useQueryClient } from "@tanstack/vue-query";
import { Clock3, Flag, ShieldAlert, Users2 } from "lucide-vue-next";
import { RouterLink } from "vue-router";
import { Button } from "@/components/ui/button";
import AdminLayout from "@/components/admin/AdminLayout.vue";
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

const { data } = useQuery({
  queryKey: ["admin", "pending-jobs", page],
  queryFn: () => getPendingJobsApi(page.value),
  keepPreviousData: true,
});

const jobs = computed<JobListing[]>(() => data.value?.data?.data ?? []);
const meta = computed(() => data.value?.data?.meta);
const topJobs = computed(() => jobs.value.slice(0, 2));

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

const flaggedComments = [
  {
    id: 1,
    title: "Reported by 3 users",
    time: "2h ago",
    body: "This company is a complete scam, don't apply here. The CEO is a known fraud.",
  },
  {
    id: 2,
    title: "Spam Filter",
    time: "5h ago",
    body: "Make $5000 a week working from home! Click here: http://suspicious-link.com",
  },
];

const recentUsers = [
  { id: 1, name: "Jane Doe", role: "Recruiter", status: "Active" },
  { id: 2, name: "Mike Smith", role: "Candidate", status: "Suspended" },
];
</script>

<template>
  <AdminLayout
    title="Platform Overview"
    subtitle="Monitor activity and manage content across WorkHive."
  >
    <template #actions>
      <Button variant="outline" size="sm">Export Data</Button>
      <Button size="sm">New Report</Button>
    </template>

    <div class="grid gap-lg">
      <div class="grid gap-md md:grid-cols-3">
        <div class="rounded-xl border border-outline-variant bg-card p-md shadow-soft">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-on-surface-variant">Pending Approvals</p>
              <p class="text-3xl font-semibold text-on-surface">{{ totalPending }}</p>
              <p class="mt-xs text-xs text-destructive">+12% from yesterday</p>
            </div>
            <div class="rounded-full bg-accent p-2 text-primary">
              <Clock3 class="h-5 w-5" aria-hidden="true" />
            </div>
          </div>
        </div>
        <div class="rounded-xl border border-outline-variant bg-card p-md shadow-soft">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-on-surface-variant">Active Users</p>
              <p class="text-3xl font-semibold text-on-surface">8.4k</p>
              <p class="mt-xs text-xs text-secondary">+5% this week</p>
            </div>
            <div class="rounded-full bg-secondary/10 p-2 text-secondary">
              <Users2 class="h-5 w-5" aria-hidden="true" />
            </div>
          </div>
        </div>
        <div class="rounded-xl border border-outline-variant bg-card p-md shadow-soft">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-on-surface-variant">Flagged Comments</p>
              <p class="text-3xl font-semibold text-on-surface">28</p>
              <p class="mt-xs text-xs text-on-surface-variant">Requires review today</p>
            </div>
            <div class="rounded-full bg-destructive/10 p-2 text-destructive">
              <Flag class="h-5 w-5" aria-hidden="true" />
            </div>
          </div>
        </div>
      </div>

      <div class="grid gap-lg lg:grid-cols-[minmax(0,1fr)_320px]">
        <div class="grid gap-lg">
          <section class="rounded-2xl border border-outline-variant bg-card shadow-soft">
            <header class="flex items-center justify-between border-b border-outline-variant px-lg py-md">
              <div>
                <h2 class="text-lg font-semibold text-on-surface">Pending Job Approvals</h2>
                <p class="text-sm text-on-surface-variant">Review the latest job listings.</p>
              </div>
              <RouterLink
                class="text-sm font-medium text-primary hover:underline"
                to="/admin/pending-jobs"
              >
                View All
              </RouterLink>
            </header>

            <div v-if="!topJobs.length" class="px-lg py-lg text-sm text-on-surface-variant">
              No pending jobs right now.
            </div>
            <div v-else class="divide-y divide-outline-variant">
              <div v-for="job in topJobs" :key="job.id" class="px-lg py-md">
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
                      class="border-destructive text-destructive hover:bg-destructive/10"
                      :disabled="rejectMutation.isPending"
                      @click="openReject(job.id)"
                    >
                      Reject
                    </Button>
                    <Button
                      size="sm"
                      :disabled="approveMutation.isPending"
                      @click="approveMutation.mutate(job.id)"
                    >
                      Approve
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

          <section class="rounded-2xl border border-outline-variant bg-card shadow-soft">
            <header class="flex items-center justify-between border-b border-outline-variant px-lg py-md">
              <div>
                <h2 class="text-lg font-semibold text-on-surface">Recent User Activity</h2>
                <p class="text-sm text-on-surface-variant">Latest account status changes.</p>
              </div>
            </header>
            <div class="px-lg py-md">
              <table class="min-w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-on-surface-variant">
                  <tr>
                    <th class="py-sm font-medium">User</th>
                    <th class="py-sm font-medium">Role</th>
                    <th class="py-sm font-medium">Status</th>
                    <th class="py-sm text-right font-medium">Action</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                  <tr v-for="user in recentUsers" :key="user.id">
                    <td class="py-md font-semibold text-on-surface">{{ user.name }}</td>
                    <td class="py-md text-on-surface-variant">{{ user.role }}</td>
                    <td class="py-md">
                      <span
                        class="rounded-full px-sm py-[2px] text-xs font-medium"
                        :class="user.status === 'Active'
                          ? 'bg-secondary/10 text-secondary'
                          : 'bg-warning/10 text-warning'"
                      >
                        {{ user.status }}
                      </span>
                    </td>
                    <td class="py-md text-right text-on-surface-variant">...</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
        </div>

        <div class="grid gap-lg">
          <section class="rounded-2xl border border-outline-variant bg-card shadow-soft">
            <header class="flex items-center justify-between border-b border-outline-variant px-lg py-md">
              <div class="flex items-center gap-xs">
                <ShieldAlert class="h-4 w-4 text-destructive" aria-hidden="true" />
                <h2 class="text-lg font-semibold text-on-surface">Flagged Comments</h2>
              </div>
            </header>

            <div class="divide-y divide-outline-variant">
              <div v-for="comment in flaggedComments" :key="comment.id" class="px-lg py-md">
                <div class="flex items-center justify-between text-xs text-on-surface-variant">
                  <span>{{ comment.title }}</span>
                  <span>{{ comment.time }}</span>
                </div>
                <p class="mt-sm text-sm text-on-surface">
                  "{{ comment.body }}"
                </p>
                <div class="mt-sm flex items-center gap-sm">
                  <Button
                    variant="outline"
                    size="sm"
                    class="border-destructive text-destructive hover:bg-destructive/10"
                  >
                    Delete
                  </Button>
                  <Button variant="outline" size="sm">Ignore</Button>
                </div>
              </div>
            </div>
          </section>

          <section class="rounded-2xl border border-outline-variant bg-card p-lg shadow-soft">
            <h2 class="text-lg font-semibold text-on-surface">Platform Health</h2>

            <div class="mt-md space-y-md">
              <div>
                <div class="mb-xs flex items-center justify-between text-xs text-on-surface-variant">
                  <span>Server Uptime</span>
                  <span class="text-secondary">99.9%</span>
                </div>
                <div class="h-2 w-full rounded-full bg-surface-container-low">
                  <div class="h-2 w-[92%] rounded-full bg-secondary"></div>
                </div>
              </div>
              <div>
                <div class="mb-xs flex items-center justify-between text-xs text-on-surface-variant">
                  <span>API Response Time</span>
                  <span class="text-primary">124ms</span>
                </div>
                <div class="h-2 w-full rounded-full bg-surface-container-low">
                  <div class="h-2 w-[74%] rounded-full bg-primary"></div>
                </div>
              </div>
              <div>
                <div class="mb-xs flex items-center justify-between text-xs text-on-surface-variant">
                  <span>Database Load</span>
                  <span class="text-destructive">85%</span>
                </div>
                <div class="h-2 w-full rounded-full bg-surface-container-low">
                  <div class="h-2 w-[85%] rounded-full bg-destructive"></div>
                </div>
              </div>
            </div>
          </section>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
