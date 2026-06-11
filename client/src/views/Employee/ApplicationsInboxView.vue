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
const showResumeId = ref<number | null>(null);

function toggleExpand(id: number) {
  expandedId.value = expandedId.value === id ? null : id;
  showResumeId.value = null; // reset resume preview state when toggling different cards
}

function toggleResumePreview(id: number) {
  showResumeId.value = showResumeId.value === id ? null : id;
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

const statusStyles: Record<string, { bg: string; text: string; border: string }> = {
  pending: { bg: "bg-warning/10", text: "text-warning", border: "border-warning/30" },
  accepted: { bg: "bg-info/10", text: "text-info", border: "border-info/30" },
  rejected: { bg: "bg-destructive/10", text: "text-destructive", border: "border-destructive/30" },
  paid: { bg: "bg-success/10", text: "text-success", border: "border-success/30" },
};

function getStatusStyle(status: string) {
  return statusStyles[status] ?? { bg: "bg-surface-container", text: "text-on-surface-variant", border: "border-outline-variant" };
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
          <p class="mt-1 font-body-md text-body-md leading-relaxed text-on-surface-variant">
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
        <p class="text-sm leading-relaxed text-on-surface-variant">Failed to load applications. Please try again.</p>
      </div>

      <!-- Empty -->
      <div v-else-if="!applications.length" class="rounded-xl border border-outline-variant bg-surface-container-lowest p-12 text-center">
        <Inbox class="mx-auto mb-3 h-12 w-12 text-on-surface-variant/40" />
        <h3 class="mb-1 text-lg font-semibold text-on-surface">No applications yet</h3>
        <p class="text-sm leading-relaxed text-on-surface-variant">Applications from candidates will appear here.</p>
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
              <img
                v-if="app.candidate?.avatar_url"
                :src="app.candidate?.avatar_url || ''"
                class="h-10 w-10 shrink-0 rounded-full object-cover border border-outline-variant"
                alt="Avatar"
              />
              <div v-else class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary">
                {{ app.candidate?.name?.charAt(0) || "?" }}
              </div>
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <span class="truncate font-label-lg text-label-lg text-on-background">{{ app.candidate?.name || "Unknown" }}</span>
                  <span
                    class="inline-flex shrink-0 items-center rounded-full border px-2.5 py-0.5 text-[10px] font-semibold capitalize"
                    :class="[getStatusStyle(app.status).bg, getStatusStyle(app.status).text, getStatusStyle(app.status).border]"
                  >
                    {{ app.status }}
                  </span>
                </div>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-on-surface-variant">
                  <span class="flex items-center gap-1 font-medium">
                    <Briefcase class="h-3 w-3 text-primary" />
                    Applied for: <span class="text-on-background font-semibold">{{ app.job?.title || "Unknown Job" }}</span>
                  </span>
                  <span class="text-outline-variant/60">•</span>
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
                class="gap-1 rounded-lg bg-secondary text-on-secondary hover:bg-secondary/90 shadow-soft"
                @click="acceptMutation.mutate(app.id)"
              >
                <CheckCircle class="h-3.5 w-3.5" />
                Accept
              </Button>
              <Button
                variant="outline"
                size="sm"
                class="gap-1 rounded-lg border-destructive/20 text-destructive hover:bg-destructive/10"
                @click="rejectMutation.mutate(app.id)"
              >
                <XCircle class="h-3.5 w-3.5" />
                Reject
              </Button>
            </div>
          </div>

          <!-- Expanded details -->
          <Transition
            enter-active-class="transition-all duration-300 ease-out"
            leave-active-class="transition-all duration-200 ease-in"
            enter-from-class="max-h-0 opacity-0"
            enter-to-class="max-h-[1500px] opacity-100"
            leave-from-class="max-h-[1500px] opacity-100"
            leave-to-class="max-h-0 opacity-0"
          >
            <div v-if="expandedId === app.id" class="overflow-hidden border-t border-outline-variant bg-surface p-5">
              <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Left Column: Candidate info -->
                <div class="space-y-4">
                  <div class="flex items-center gap-2 pb-2 border-b border-outline-variant/60">
                    <User class="h-4 w-4 text-primary" />
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-on-surface-variant">Candidate Information</h4>
                  </div>
                  
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-xl p-3 flex flex-col gap-1">
                      <span class="text-[10px] text-outline uppercase tracking-wider font-semibold">Location</span>
                      <span class="text-on-surface font-medium flex items-center gap-1">
                        <MapPin class="h-3.5 w-3.5 text-outline" />
                        {{ app.candidate?.profile?.location || "Not specified" }}
                      </span>
                    </div>

                    <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-xl p-3 flex flex-col gap-1">
                      <span class="text-[10px] text-outline uppercase tracking-wider font-semibold">Experience</span>
                      <span class="text-on-surface font-medium">
                        {{ app.candidate?.profile?.experience_years ? `${app.candidate.profile.experience_years} Years` : "Not specified" }}
                      </span>
                    </div>
                  </div>

                  <!-- Skills -->
                  <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-xl p-3 space-y-2">
                    <span class="text-[10px] text-outline uppercase tracking-wider font-semibold block">Key Skills</span>
                    <div v-if="app.candidate?.profile?.skills?.length" class="flex flex-wrap gap-1">
                      <span
                        v-for="skill in app.candidate?.profile?.skills"
                        :key="skill"
                        class="rounded-lg bg-primary/10 px-2.5 py-0.5 text-xs text-primary font-medium"
                      >
                        {{ skill }}
                      </span>
                    </div>
                    <span v-else class="text-xs text-on-surface-variant italic">No skills listed</span>
                  </div>

                  <!-- Unlocked details (Only visible if status is paid) -->
                  <div v-if="app.status === 'paid'" class="bg-success-container/10 border border-success/20 rounded-xl p-4 space-y-3">
                    <div class="flex items-center gap-2 pb-1 border-b border-success/10">
                      <Unlock class="h-4 w-4 text-success" />
                      <span class="text-xs font-semibold text-success uppercase tracking-wider">Unlocked Details</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                      <div class="flex items-center gap-2 bg-surface-container-lowest border border-outline-variant/40 rounded-lg p-2">
                        <Mail class="h-3.5 w-3.5 text-primary flex-shrink-0" />
                        <a :href="`mailto:${app.candidate?.email}`" class="text-primary hover:underline font-medium truncate">
                          {{ app.candidate?.email }}
                        </a>
                      </div>
                      <div v-if="app.candidate?.profile?.phone" class="flex items-center gap-2 bg-surface-container-lowest border border-outline-variant/40 rounded-lg p-2">
                        <Phone class="h-3.5 w-3.5 text-primary flex-shrink-0" />
                        <a :href="`tel:${app.candidate?.profile?.phone}`" class="text-primary hover:underline font-medium">
                          {{ app.candidate?.profile?.phone }}
                        </a>
                      </div>
                    </div>
                  </div>

                  <!-- Locked contact CTA (Visible if status is accepted but not paid) -->
                  <div v-else-if="app.status === 'accepted'" class="rounded-xl border border-primary/20 bg-primary/5 p-4 flex flex-col items-start gap-2 shadow-sm">
                    <p class="text-xs font-medium text-primary flex items-center gap-1.5">
                      <Lock class="h-4 w-4" />
                      Candidate contact details & resume are locked.
                    </p>
                    <RouterLink :to="`/payment/checkout/${app.id}`" class="w-full">
                      <Button size="sm" class="w-full rounded-lg bg-primary text-on-primary hover:bg-primary/95 shadow-soft hover:shadow-elegant">
                        Unlock Contact Details
                      </Button>
                    </RouterLink>
                  </div>
                </div>

                <!-- Right Column: Cover Letter -->
                <div class="flex flex-col">
                  <div class="flex items-center gap-2 pb-2 mb-3 border-b border-outline-variant/60">
                    <FileText class="h-4.5 w-4.5 text-primary" />
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-on-surface-variant">Cover Letter</h4>
                  </div>
                  <div class="flex-grow rounded-xl border border-outline-variant bg-surface-container-lowest p-4 min-h-[150px] shadow-sm">
                    <p v-if="app.cover_letter" class="whitespace-pre-line text-sm leading-relaxed text-on-surface-variant">
                      {{ app.cover_letter }}
                    </p>
                    <p v-else class="text-sm leading-relaxed text-on-surface-variant italic">
                      No cover letter was submitted with this application.
                    </p>
                  </div>
                </div>
              </div>

              <!-- Resume Preview block for Unlocked (Paid) application -->
              <div v-if="app.status === 'paid'" class="mt-6 border-t border-outline-variant/60 pt-6">
                <div class="flex items-center justify-between mb-3 bg-surface-container-low/40 rounded-xl p-3 border border-outline-variant/40">
                  <div class="flex items-center gap-2">
                    <FileText class="h-5 w-5 text-primary" />
                    <div>
                      <h4 class="text-sm font-semibold text-on-background">Candidate Resume</h4>
                      <p class="text-[11px] text-on-surface-variant font-medium">Unlocked candidate's PDF resume details</p>
                    </div>
                  </div>
                  <div class="flex gap-2">
                    <Button
                      v-if="app.candidate?.profile?.resume_url"
                      size="sm"
                      variant="outline"
                      class="gap-1 rounded-lg text-xs"
                      @click="toggleResumePreview(app.id)"
                    >
                      {{ showResumeId === app.id ? 'Hide Preview' : 'Preview Resume' }}
                    </Button>
                    <Button
                      v-if="app.candidate?.profile?.resume_url"
                      as="a"
                      :href="app.candidate?.profile?.resume_url"
                      target="_blank"
                      size="sm"
                      variant="secondary"
                      class="gap-1 rounded-lg text-xs"
                    >
                      <Unlock class="h-3.5 w-3.5" />
                      Open Fullscreen
                    </Button>
                    <span v-else class="text-xs text-on-surface-variant italic py-1">No resume uploaded by candidate.</span>
                  </div>
                </div>
                <!-- Inline Frame Preview -->
                <div v-if="showResumeId === app.id && app.candidate?.profile?.resume_url" class="mt-3 border border-outline-variant/65 rounded-2xl overflow-hidden shadow-elegant bg-white dark:bg-zinc-950">
                  <iframe :src="app.candidate?.profile?.resume_url" class="w-full h-[600px] border-0 animate-fade-in" allow="autoplay"></iframe>
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
