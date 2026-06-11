<script setup lang="ts">
import { computed, ref } from "vue";
import { useQuery, useMutation, useQueryClient } from "@tanstack/vue-query";
import { FileText, Clock, Building2, MapPin, Briefcase, Trash2, ChevronLeft, ChevronRight } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { toast } from "vue-sonner";
import { getMyApplicationsApi, cancelApplicationApi } from "@/api/jobs";

const page = ref(1);
const queryClient = useQueryClient();

const { data, isPending, isError } = useQuery({
  queryKey: ["my-applications", page],
  queryFn: () => getMyApplicationsApi(page.value),
});

type AppData = {
  id: number;
  status: string;
  cover_letter?: string | null;
  created_at: string;
  job?: {
    id: number;
    title: string;
    location?: string;
    work_type?: string;
    company_name?: string;
    category?: string;
  };
};

const applications = computed<AppData[]>(() => data.value?.data?.data ?? []);
const meta = computed(() => data.value?.data?.meta ?? null);

const cancelMutation = useMutation({
  mutationFn: (id: number) => cancelApplicationApi(id),
  onSuccess: () => {
    toast.success("Application cancelled.");
    queryClient.invalidateQueries({ queryKey: ["my-applications"] });
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || "Failed to cancel application.");
  },
});

function confirmCancel(app: AppData) {
  if (window.confirm(`Cancel your application for "${app.job?.title}"?`)) {
    cancelMutation.mutate(app.id);
  }
}

const statusStyles: Record<string, { bg: string; text: string }> = {
  pending: { bg: "bg-amber-50 border-amber-200", text: "text-amber-700" },
  accepted: { bg: "bg-green-50 border-green-200", text: "text-green-700" },
  rejected: { bg: "bg-red-50 border-red-200", text: "text-red-700" },
  paid: { bg: "bg-blue-50 border-blue-200", text: "text-blue-700" },
};

function getStatusStyle(status: string) {
  return statusStyles[status] ?? { bg: "bg-surface-container", text: "text-on-surface-variant" };
}

function formatDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}
</script>

<template>
  <div class="min-h-screen bg-background text-on-background">
    <main class="mx-auto w-full max-w-container-max flex-1 p-md md:p-lg">
      <div class="mb-xl">
        <h1 class="font-headline-lg text-headline-lg text-on-background">My Applications</h1>
        <p class="mt-1 font-body-md text-body-md text-on-surface-variant">
          Track and manage your job applications.
        </p>
      </div>

      <!-- Loading -->
      <div v-if="isPending" class="space-y-4">
        <div v-for="i in 4" :key="i" class="animate-pulse rounded-xl border border-outline-variant bg-surface-container-lowest p-5">
          <div class="mb-2 h-5 w-2/3 rounded bg-surface-container-low" />
          <div class="h-4 w-1/3 rounded bg-surface-container-low" />
        </div>
      </div>

      <!-- Error -->
      <div v-else-if="isError" class="rounded-xl border border-outline-variant bg-surface-container-lowest p-8 text-center">
        <p class="text-sm text-on-surface-variant">Failed to load applications. Please try again.</p>
      </div>

      <!-- Empty -->
      <div v-else-if="!applications.length" class="rounded-xl border border-outline-variant bg-surface-container-lowest p-12 text-center">
        <FileText class="mx-auto mb-3 h-12 w-12 text-on-surface-variant/40" />
        <h3 class="mb-1 text-lg font-semibold text-on-surface">No applications yet</h3>
        <p class="text-sm text-on-surface-variant">Start browsing jobs and apply to get started.</p>
      </div>

      <!-- Applications list -->
      <div v-else class="space-y-3">
        <article
          v-for="app in applications"
          :key="app.id"
          class="flex flex-col gap-4 rounded-xl border border-outline-variant bg-surface-container-lowest p-5 shadow-sm transition-shadow hover:shadow-md sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="min-w-0 flex-1">
            <div class="mb-1 flex items-center gap-2">
              <h3 class="truncate font-label-lg text-label-lg text-on-background">
                {{ app.job?.title || "Unknown Position" }}
              </h3>
              <span
                class="inline-flex shrink-0 items-center rounded-full border px-2.5 py-0.5 text-xs font-medium capitalize"
                :class="[getStatusStyle(app.status).bg, getStatusStyle(app.status).text]"
              >
                {{ app.status }}
              </span>
            </div>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-on-surface-variant">
              <span v-if="app.job?.company_name" class="flex items-center gap-1">
                <Building2 class="h-3 w-3" />
                {{ app.job.company_name }}
              </span>
              <span v-if="app.job?.location" class="flex items-center gap-1">
                <MapPin class="h-3 w-3" />
                {{ app.job.location }}
              </span>
              <span v-if="app.job?.work_type" class="flex items-center gap-1">
                <Briefcase class="h-3 w-3" />
                {{ app.job.work_type }}
              </span>
              <span class="flex items-center gap-1">
                <Clock class="h-3 w-3" />
                Applied {{ formatDate(app.created_at) }}
              </span>
            </div>
          </div>

          <div class="flex shrink-0 gap-2">
            <Button
              v-if="app.status === 'pending'"
              variant="outline"
              size="sm"
              class="gap-1 rounded-lg border-red-200 text-red-600 hover:bg-red-50"
              @click="confirmCancel(app)"
            >
              <Trash2 class="h-3.5 w-3.5" />
              Cancel
            </Button>
          </div>
        </article>
      </div>

      <!-- Pagination -->
      <div v-if="meta && meta.last_page > 1" class="mt-xl flex items-center justify-center gap-2">
        <Button variant="outline" size="sm" :disabled="page <= 1" @click="page--">
          <ChevronLeft class="h-4 w-4" />
          Previous
        </Button>
        <span class="px-3 text-sm text-on-surface-variant">
          Page {{ meta.current_page }} of {{ meta.last_page }}
        </span>
        <Button variant="outline" size="sm" :disabled="page >= meta.last_page" @click="page++">
          Next
          <ChevronRight class="h-4 w-4" />
        </Button>
      </div>
    </main>
  </div>
</template>
