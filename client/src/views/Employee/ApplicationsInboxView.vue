<script setup lang="ts">
import { computed, ref } from "vue";
import { RouterLink } from "vue-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/vue-query";
import { CheckCircle, XCircle, Inbox, User, MapPin, Clock, ChevronLeft, ChevronRight, Briefcase, Mail, Lock, Unlock, Phone, FileText } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { toast } from "vue-sonner";
import api from "@/api/api";

const page = ref(1);
const statusFilter = ref("");
const queryClient = useQueryClient();

const { data, isPending, isError } = useQuery({
  queryKey: ["employer-applications", page, statusFilter],
  queryFn: async () => {
    const params: Record<string, unknown> = { page: page.value };
    if (statusFilter.value) params.status = statusFilter.value;
    const res = await api.get("/employer/applications", { params });
    return res.data;
  },
});

type AppData = {
  id: number;
  status: string;
  cover_letter?: string | null;
  created_at: string;
  candidate?: {
    id: number;
    name: string;
    email: string;
    avatar_url?: string | null;
    profile?: {
      location?: string;
      experience_years?: number;
      skills?: string[];
      phone?: string;
      resume_url?: string;
    };
  };
  job?: {
    id: number;
    title: string;
    location?: string;
    work_type?: string;
  };
};

const applications = computed<AppData[]>(() => data.value?.data?.data ?? []);
const meta = computed(() => data.value?.data?.meta ?? null);

const expandedId = ref<number | null>(null);
function toggleExpand(id: number) {
  expandedId.value = expandedId.value === id ? null : id;
}

const acceptMutation = useMutation({
  mutationFn: (id: number) => api.patch(`/employer/applications/${id}/accept`),
  onSuccess: () => {
    toast.success("Application accepted!");
    queryClient.invalidateQueries({ queryKey: ["employer-applications"] });
  },
  onError: () => toast.error("Failed to accept application."),
});

const rejectMutation = useMutation({
  mutationFn: (id: number) => api.patch(`/employer/applications/${id}/reject`),
  onSuccess: () => {
    toast.success("Application rejected.");
    queryClient.invalidateQueries({ queryKey: ["employer-applications"] });
  },
  onError: () => toast.error("Failed to reject application."),
});

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
      <div class="mb-xl flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="font-headline-lg text-headline-lg text-on-background">Applications Inbox</h1>
          <p class="mt-1 font-body-md text-body-md text-on-surface-variant">
            Review and manage applications to your job postings.
          </p>
        </div>
        <select
          v-model="statusFilter"
          class="w-auto rounded-lg border border-outline-variant bg-surface-container-lowest px-4 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
        >
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="accepted">Accepted</option>
          <option value="rejected">Rejected</option>
          <option value="paid">Unlocked (Paid)</option>
        </select>
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
        <Inbox class="mx-auto mb-3 h-12 w-12 text-on-surface-variant/40" />
        <h3 class="mb-1 text-lg font-semibold text-on-surface">No applications yet</h3>
        <p class="text-sm text-on-surface-variant">Applications from candidates will appear here.</p>
      </div>

      <!-- Applications list -->
      <div v-else class="space-y-3">
        <article
          v-for="app in applications"
          :key="app.id"
          class="overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest shadow-sm transition-shadow hover:shadow-md"
        >
          <!-- Header row -->
          <div
            class="flex cursor-pointer flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between"
            @click="toggleExpand(app.id)"
          >
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary">
                {{ app.candidate?.name?.charAt(0) || "?" }}
              </div>
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <span class="truncate font-label-lg text-label-lg text-on-background">{{ app.candidate?.name || "Unknown" }}</span>
                  <span
                    class="inline-flex shrink-0 items-center rounded-full border px-2 py-0.5 text-[10px] font-medium capitalize"
                    :class="[getStatusStyle(app.status).bg, getStatusStyle(app.status).text]"
                  >
                    {{ app.status }}
                  </span>
                </div>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-on-surface-variant">
                  <span class="flex items-center gap-1">
                    <Briefcase class="h-3 w-3" />
                    {{ app.job?.title || "Unknown Job" }}
                  </span>
                  <span class="flex items-center gap-1">
                    <Clock class="h-3 w-3" />
                    {{ formatDate(app.created_at) }}
                  </span>
                </div>
              </div>
            </div>

            <div v-if="app.status === 'pending'" class="flex gap-2" @click.stop>
              <Button
                size="sm"
                class="gap-1 rounded-lg bg-green-600 text-white hover:bg-green-700"
                @click="acceptMutation.mutate(app.id)"
              >
                <CheckCircle class="h-3.5 w-3.5" />
                Accept
              </Button>
              <Button
                variant="outline"
                size="sm"
                class="gap-1 rounded-lg border-red-200 text-red-600 hover:bg-red-50"
                @click="rejectMutation.mutate(app.id)"
              >
                <XCircle class="h-3.5 w-3.5" />
                Reject
              </Button>
            </div>
          </div>

          <!-- Expanded details -->
          <Transition
            enter-active-class="transition-all duration-200 ease-out"
            leave-active-class="transition-all duration-150 ease-in"
            enter-from-class="max-h-0 opacity-0"
            enter-to-class="max-h-96 opacity-100"
            leave-from-class="max-h-96 opacity-100"
            leave-to-class="max-h-0 opacity-0"
          >
            <div v-if="expandedId === app.id" class="overflow-hidden border-t border-outline-variant bg-surface p-5">
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-on-surface-variant">Candidate Info</h4>
                  <div class="space-y-2 text-sm">
                    <!-- Basic details (Always visible) -->
                    <p v-if="app.candidate?.profile?.location" class="flex items-center gap-1.5">
                      <MapPin class="h-3.5 w-3.5 text-on-surface-variant" />
                      {{ app.candidate.profile.location }}
                    </p>
                    <p v-if="app.candidate?.profile?.experience_years">
                      <strong>Experience:</strong> {{ app.candidate.profile.experience_years }} years
                    </p>
                    <div v-if="app.candidate?.profile?.skills?.length" class="flex flex-wrap gap-1">
                      <span
                        v-for="skill in app.candidate.profile.skills"
                        :key="skill"
                        class="rounded-md bg-primary/8 px-2 py-0.5 text-xs text-primary"
                      >
                        {{ skill }}
                      </span>
                    </div>

                    <!-- Unlocked details (Only visible if status is paid) -->
                    <div v-if="app.status === 'paid'" class="mt-2 space-y-2 border-t border-outline-variant pt-2">
                      <p v-if="app.candidate?.email" class="flex items-center gap-1.5">
                        <Mail class="h-3.5 w-3.5 text-on-surface-variant" />
                        <a :href="`mailto:${app.candidate.email}`" class="text-primary hover:underline">{{ app.candidate.email }}</a>
                      </p>
                      <p v-if="app.candidate?.profile?.phone" class="flex items-center gap-1.5">
                        <Phone class="h-3.5 w-3.5 text-on-surface-variant" />
                        <a :href="`tel:${app.candidate.profile.phone}`" class="text-primary hover:underline">{{ app.candidate.profile.phone }}</a>
                      </p>
                      <p v-if="app.candidate?.profile?.resume_url" class="flex items-center gap-1.5 font-medium">
                        <FileText class="h-3.5 w-3.5 text-on-surface-variant" />
                        <a :href="app.candidate.profile.resume_url" target="_blank" class="text-primary hover:underline">
                          Download/View Resume
                        </a>
                      </p>
                    </div>

                    <!-- Locked contact CTA (Visible if status is accepted but not paid) -->
                    <div v-else-if="app.status === 'accepted'" class="mt-4 rounded-xl border border-primary/20 bg-primary/5 p-4 flex flex-col items-start gap-2">
                      <p class="text-xs font-medium text-primary flex items-center gap-1">
                        <Lock class="h-3.5 w-3.5" />
                        Candidate contact details & resume are locked.
                      </p>
                      <RouterLink :to="`/payment/checkout/${app.id}`">
                        <Button size="sm" class="rounded-lg">
                          Unlock Contact Details
                        </Button>
                      </RouterLink>
                    </div>
                  </div>
                </div>
                <div v-if="app.cover_letter">
                  <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-on-surface-variant">Cover Letter</h4>
                  <p class="whitespace-pre-line text-sm text-on-surface-variant">{{ app.cover_letter }}</p>
                </div>
              </div>
            </div>
          </Transition>
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
