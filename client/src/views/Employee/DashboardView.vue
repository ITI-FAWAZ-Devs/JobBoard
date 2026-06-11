<script setup lang="ts">
import { computed, ref } from "vue";
import { useQuery, useMutation, useQueryClient } from "@tanstack/vue-query";
import { Plus, Eye, FileText, UserCheck, Search, Filter, Pencil, Trash2, TrendingUp } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { RouterLink } from "vue-router";
import { toast } from "vue-sonner";
import { getEmployerAnalyticsApi, getEmployerJobsApi, type JobListing } from "@/api/employer";
import api from "@/api/api";

const page = ref(1);

const analyticsQuery = useQuery({
  queryKey: ["employer", "analytics"],
  queryFn: () => getEmployerAnalyticsApi(),
});

const jobsQuery = useQuery({
  queryKey: ["employer", "jobs", page],
  queryFn: () => getEmployerJobsApi(page.value),
});

const analytics = computed(() => {
  const d = analyticsQuery.data;
  if (!d) return null;
  if ("data" in d && d.data && typeof d.data === "object" && "views" in d.data) return d.data as { views: number; applicants: number; conversion_rate: number };
  return null;
});

const jobs = computed<JobListing[]>(() => {
  const d = jobsQuery.data;
  if (!d) return [];
  if (Array.isArray(d)) return d as JobListing[];
  const inner = (d as any)?.data;
  if (Array.isArray(inner)) return inner as JobListing[];
  const innerData = (d as any)?.data?.data;
  if (Array.isArray(innerData)) return innerData as JobListing[];
  return [];
});

const totalJobs = computed(() => jobs.value.length);

const statCards = computed(() => [
  { label: "Total Profile Views", value: analytics.value?.views?.toLocaleString() ?? "—", icon: Eye, trend: "—", muted: true },
  { label: "Total Applications", value: analytics.value?.applicants?.toLocaleString() ?? "—", icon: FileText, trend: "—", muted: true },
  { label: "Conversion Rate", value: analytics.value ? `${(analytics.value.conversion_rate * 100).toFixed(1)}%` : "—", icon: UserCheck, trend: "—", muted: true },
]);

const statusStyles: Record<string, { cls: string; dot: string }> = {
  approved: { cls: "bg-surface-container text-primary", dot: "bg-primary" },
  pending: { cls: "bg-surface-variant text-on-surface-variant", dot: "bg-on-surface-variant" },
  rejected: { cls: "bg-error-container text-on-error-container", dot: "bg-error" },
};

function getStatusStyle(status: string) {
  return statusStyles[status] ?? { cls: "bg-surface-variant text-on-surface-variant", dot: "bg-on-surface-variant" };
}

function formatDate(dateStr?: string | null) {
  if (!dateStr) return "—";
  return new Date(dateStr).toLocaleDateString("en-US", { year: "numeric", month: "short", day: "numeric" });
}

const queryClient = useQueryClient();
const deleteMutation = useMutation({
  mutationFn: async (id: number) => {
    await api.delete(`/employer/jobs/${id}`);
  },
  onSuccess: () => {
    toast.success("Job deleted.");
    queryClient.invalidateQueries({ queryKey: ["employer", "jobs"] });
  },
  onError: () => toast.error("Failed to delete job."),
});

function confirmDelete(job: JobListing) {
  if (window.confirm(`Delete "${job.title}"? This cannot be undone.`)) {
    deleteMutation.mutate(job.id);
  }
}
</script>

<template>
  <div class="min-h-screen bg-background text-on-background">
    <main class="flex min-h-screen flex-1 flex-col">
      <div class="mx-auto w-full max-w-container-max flex-1 p-md md:p-lg">
        <div class="mb-xl flex flex-col justify-between gap-sm md:flex-row md:items-center">
          <div>
            <h1 class="font-headline-lg text-headline-lg text-on-background">Overview</h1>
            <p class="mt-1 font-body-md text-body-md leading-relaxed text-on-surface-variant">
              Welcome back. Here's what's happening with your job postings today.
            </p>
          </div>
          <RouterLink
            to="/employer/jobs/create"
            class="flex w-full cursor-pointer items-center justify-center gap-xs rounded-lg bg-primary-container px-md py-sm font-label-md text-label-md text-on-primary md:w-auto"
          >
            <Plus class="h-5 w-5" aria-hidden="true" />
            Post a Job
          </RouterLink>
        </div>

        <section class="mb-xl grid grid-cols-1 gap-md md:grid-cols-3">
          <article
            v-for="stat in statCards"
            :key="stat.label"
            class="flex flex-col justify-between rounded-xl bg-surface-container-lowest p-md shadow-[0px_4px_12px_rgba(0,0,0,0.05)]"
          >
            <div class="mb-sm flex items-start justify-between">
              <div class="flex h-10 w-10 items-center justify-center rounded-full bg-surface-container text-primary">
                <component :is="stat.icon" class="h-5 w-5" aria-hidden="true" />
              </div>
              <span class="flex items-center gap-1 rounded-full bg-surface-container-high px-2 py-1 font-label-sm text-label-sm text-on-surface-variant">
                <TrendingUp class="h-3.5 w-3.5" aria-hidden="true" />
                {{ stat.trend }}
              </span>
            </div>
            <div>
              <p class="mb-1 font-label-md text-label-md text-on-surface-variant">{{ stat.label }}</p>
              <h3 class="font-headline-xl text-headline-xl text-on-background">{{ stat.value }}</h3>
            </div>
          </article>
        </section>

        <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
          <div class="flex flex-col items-start justify-between gap-sm border-b border-outline-variant p-md sm:flex-row sm:items-center">
            <h2 class="font-headline-md text-headline-md text-on-background">My Job Listings</h2>
            <div class="flex w-full gap-sm sm:w-auto">
              <div class="relative w-full sm:w-64">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-on-surface-variant" aria-hidden="true" />
                <input
                  class="w-full rounded-lg border border-outline-variant bg-surface-container-lowest py-2 pl-10 pr-4 font-body-sm text-body-sm placeholder:text-on-surface-variant focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                  placeholder="Search listings..."
                  type="text"
                />
              </div>
              <Button variant="outline" size="icon" class="cursor-pointer rounded-lg border-outline-variant text-on-surface-variant hover:bg-surface-variant">
                <Filter class="h-4 w-4" aria-hidden="true" />
              </Button>
            </div>
          </div>

          <div v-if="!jobs.length" class="p-md font-body-sm text-body-sm text-on-surface-variant">
            No job listings yet.
          </div>
          <div v-else class="overflow-x-auto">
            <table class="w-full border-collapse text-left">
              <thead>
                <tr class="border-b border-outline-variant bg-surface">
                  <th class="p-md font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Job Title</th>
                  <th class="p-md font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Status</th>
                  <th class="p-md font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Posted Date</th>
                  <th class="p-md text-right font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Applications</th>
                  <th class="p-md text-center font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-outline-variant">
                <tr v-for="job in jobs" :key="job.id" class="group transition-colors hover:bg-surface-container-low">
                  <td class="p-md">
                    <div class="mb-1 font-label-md text-label-md text-on-background">{{ job.title }}</div>
                    <div class="font-body-sm text-body-sm text-on-surface-variant">
                      {{ job.location || "Remote" }}
                      <template v-if="job.work_type"> · {{ job.work_type }}</template>
                    </div>
                  </td>
                  <td class="p-md">
                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 font-label-sm text-label-sm" :class="getStatusStyle(job.status).cls">
                      <span class="h-1.5 w-1.5 rounded-full" :class="getStatusStyle(job.status).dot"></span>
                      {{ job.status.charAt(0).toUpperCase() + job.status.slice(1) }}
                    </span>
                  </td>
                  <td class="p-md font-body-sm text-body-sm text-on-surface-variant">{{ formatDate(job.created_at) }}</td>
                  <td class="p-md text-right font-label-md text-label-md text-on-background">{{ job.applications_count ?? 0 }}</td>
                  <td class="p-md text-center">
                    <div class="flex items-center justify-center gap-1">
                      <RouterLink
                        :to="`/employer/jobs/${job.id}/edit`"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-on-surface-variant transition-colors hover:bg-surface-variant hover:text-primary"
                        title="Edit job"
                      >
                        <Pencil class="h-4 w-4" aria-hidden="true" />
                      </RouterLink>
                      <button
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-on-surface-variant transition-colors hover:bg-error-container hover:text-error"
                        title="Delete job"
                        @click="confirmDelete(job)"
                      >
                        <Trash2 class="h-4 w-4" aria-hidden="true" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="flex items-center justify-between border-t border-outline-variant p-md font-body-sm text-body-sm text-on-surface-variant">
            <span>Showing {{ jobs.length }} listing{{ jobs.length !== 1 ? 's' : '' }}</span>
            <div class="flex gap-2">
              <Button variant="outline" class="cursor-pointer rounded-md border-outline-variant px-3 py-1 hover:bg-surface-variant" :disabled="page <= 1" @click="page--">
                Prev
              </Button>
              <Button variant="outline" class="cursor-pointer rounded-md border-outline-variant px-3 py-1 hover:bg-surface-variant" @click="page++">
                Next
              </Button>
            </div>
          </div>
        </section>
      </div>
    </main>
  </div>
</template>
