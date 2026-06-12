<script setup lang="ts">
import { computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/vue-query";
import {
  ArrowLeft,
  Briefcase,
  Building2,
  Calendar,
  CheckCircle2,
  Clock,
  DollarSign,
  Eye,
  Globe,
  MapPin,
  Send,
  Zap,
  MessageSquare,
  Flag,
  Upload,
  X,
  Bookmark,
} from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { toast } from "vue-sonner";
import Navbar from "@/components/shared/Navbar.vue";
import Footer from "@/components/shared/Footer.vue";
import {
  getJobDetailApi,
  getJobCommentsApi,
  postCommentApi,
  reportCommentApi,
  applyToJobApi,
  quickApplyApi,
  getSavedJobsApi,
  saveJobApi,
  unsaveJobApi,
  type JobListingPublic,
  type CommentData,
} from "@/api/jobs";

const route = useRoute();
const router = useRouter();
const queryClient = useQueryClient();
const jobId = Number(route.params.id);
const isLoggedIn = computed(() => Boolean(localStorage.getItem("token")));
const storedUser = computed(() => {
  try {
    return JSON.parse(localStorage.getItem("user") || "null");
  } catch {
    return null;
  }
});
const isCandidate = computed(() => storedUser.value?.role === "candidate");

// Job detail query
const { data: jobData, isPending, isError } = useQuery({
  queryKey: ["job", jobId],
  queryFn: () => getJobDetailApi(jobId),
});
const job = computed<JobListingPublic | null>(() => jobData.value?.data ?? null);

// Comments query
const { data: commentsData } = useQuery({
  queryKey: ["job-comments", jobId],
  queryFn: () => getJobCommentsApi(jobId),
});
const comments = computed<CommentData[]>(() => commentsData.value?.data ?? []);

// Apply modal state
const showApplyModal = ref(false);
const coverLetter = ref("");
const resumeFile = ref<File | null>(null);

const storedProfile = computed(() => {
  try {
    const u = JSON.parse(localStorage.getItem("user") || "null");
    return u?.profile ?? null;
  } catch {
    return null;
  }
});

const prefillName = computed(() => {
  try {
    return JSON.parse(localStorage.getItem("user") || "null")?.name ?? "";
  } catch { return ""; }
});
const prefillPhone = computed(() => storedProfile.value?.phone ?? "");
const prefillLinkedIn = computed(() => storedProfile.value?.linkedin_url ?? "");
const hasProfileResume = computed(() => Boolean(storedProfile.value?.resume_url));

function handleFileChange(e: Event) {
  const files = (e.target as HTMLInputElement).files;
  if (files?.length) resumeFile.value = files[0] || null;
}

const applyMutation = useMutation({
  mutationFn: () => {
    const fd = new FormData();
    fd.append("cover_letter", coverLetter.value);
    if (resumeFile.value) fd.append("resume", resumeFile.value);
    return applyToJobApi(jobId, fd);
  },
  onSuccess: () => {
    toast.success("Application submitted successfully!");
    showApplyModal.value = false;
    coverLetter.value = "";
    resumeFile.value = null;
    queryClient.invalidateQueries({ queryKey: ["job", jobId] });
  },
  onError: (err: any) => {
    toast.error(err?.response?.data?.message || "Failed to submit application.");
  },
});

const quickApplyMutation = useMutation({
  mutationFn: () => quickApplyApi(jobId),
  onSuccess: () => {
    toast.success("Application sent with your profile resume!");
    queryClient.invalidateQueries({ queryKey: ["job", jobId] });
  },
  onError: (err: any) => {
    const data = err?.response?.data;
    if (data?.code === "resume_required") {
      toast.error("Upload a resume to your profile first to use Quick Apply.", {
        action: {
          label: "Go to Profile",
          onClick: () => router.push("/candidate/profile"),
        },
      });
      return;
    }
    toast.error(data?.message || "Failed to submit application.");
  },
});

// Comment form
const newComment = ref("");
const commentMutation = useMutation({
  mutationFn: () => postCommentApi(jobId, newComment.value),
  onSuccess: () => {
    newComment.value = "";
    queryClient.invalidateQueries({ queryKey: ["job-comments", jobId] });
    toast.success("Comment posted!");
  },
  onError: () => toast.error("Failed to post comment."),
});

const reportMutation = useMutation({
  mutationFn: (commentId: number) => reportCommentApi(commentId),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ["job-comments", jobId] });
    toast.success("Comment reported.");
  },
  onError: () => toast.error("Failed to report comment."),
});

function formatSalary(min?: number | null, max?: number | null) {
  if (!min && !max) return null;
  const fmt = (n: number) => `$${n.toLocaleString()}`;
  if (min && max) return `${fmt(min)} – ${fmt(max)}`;
  if (min) return `From ${fmt(min)}`;
  return `Up to ${fmt(max!)}`;
}

const { data: savedData } = useQuery({
  queryKey: ["saved-jobs", 1],
  queryFn: () => getSavedJobsApi(1),
  enabled: isCandidate,
});

const isSaved = computed(() => {
  const ids = new Set((savedData.value?.data?.data ?? []).map(item => item.job?.id));
  return ids.has(jobId);
});

const toggleSaveMutation = useMutation({
  mutationFn: async () => {
    if (isSaved.value) {
      await unsaveJobApi(jobId);
    } else {
      await saveJobApi(jobId);
    }
  },
  onSuccess: () => {
    toast.success(isSaved.value ? "Job removed from saved list." : "Job saved successfully.");
    queryClient.invalidateQueries({ queryKey: ["saved-jobs"] });
    queryClient.invalidateQueries({ queryKey: ["candidate", "dashboard"] });
  },
  onError: () => {
    toast.error("Failed to update saved status.");
  },
});

function handleToggleSave() {
  if (!isLoggedIn.value) {
    router.push("/sign-in");
    return;
  }
  toggleSaveMutation.mutate();
}

function formatDate(dateStr?: string | null) {
  if (!dateStr) return "N/A";
  return new Date(dateStr).toLocaleDateString("en-US", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
}
</script>

<template>
  <div class="min-h-screen bg-surface text-on-surface antialiased">
    <Navbar />

    <main class="mx-auto w-full max-w-4xl px-4 py-8 sm:px-6">
      <!-- Back button -->
      <button
        class="mb-6 flex items-center gap-1.5 text-sm text-on-surface-variant transition-colors hover:text-on-surface"
        @click="router.back()"
      >
        <ArrowLeft class="h-4 w-4" />
        Back to Jobs
      </button>

      <!-- Loading -->
      <div v-if="isPending" class="animate-pulse space-y-4">
        <div class="h-8 w-3/4 rounded bg-surface-container-low" />
        <div class="h-5 w-1/2 rounded bg-surface-container-low" />
        <div class="h-48 rounded-xl bg-surface-container-low" />
      </div>

      <!-- Error -->
      <div v-else-if="isError || !job" class="rounded-xl border border-outline-variant bg-surface-container-lowest p-8 text-center">
        <p class="text-sm leading-relaxed text-on-surface-variant">Job not found or failed to load.</p>
        <Button variant="outline" class="mt-4" @click="router.push('/jobs')">Browse Jobs</Button>
      </div>

      <!-- Job Detail -->
      <template v-else>
        <!-- Header Card -->
        <section class="mb-6 rounded-2xl border border-outline-variant bg-surface-container-lowest p-6 shadow-sm">
          <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex gap-4">
              <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-xl font-bold text-primary">
                {{ job.employer_profile?.company_name?.charAt(0) || "?" }}
              </div>
              <div>
                <h1 class="text-2xl font-bold text-on-surface">{{ job.title }}</h1>
                <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-on-surface-variant">
                  <span class="flex items-center gap-1">
                    <Building2 class="h-4 w-4" />
                    {{ job.employer_profile?.company_name || "Company" }}
                  </span>
                  <span v-if="job.location" class="flex items-center gap-1">
                    <MapPin class="h-4 w-4" />
                    {{ job.location }}
                  </span>
                  <span class="flex items-center gap-1">
                    <Briefcase class="h-4 w-4" />
                    {{ job.work_type }}
                  </span>
                </div>
              </div>
            </div>
            <div v-if="isCandidate" class="flex gap-2 shrink-0">
              <Button
                variant="outline"
                class="gap-2 rounded-xl border-outline-variant hover:bg-surface-container-low cursor-pointer"
                @click="handleToggleSave"
              >
                <Bookmark class="h-4 w-4 text-primary" :class="{ 'fill-primary': isSaved }" />
                {{ isSaved ? 'Saved' : 'Save Job' }}
              </Button>
              <Button
                v-if="!job?.has_applied"
                variant="outline"
                class="gap-2 rounded-xl border-primary/40 text-primary hover:bg-primary/5 cursor-pointer"
                :disabled="quickApplyMutation.isPending.value"
                @click="quickApplyMutation.mutate()"
              >
                <Zap class="h-4 w-4" />
                {{ quickApplyMutation.isPending.value ? "Applying..." : "Quick Apply" }}
              </Button>
              <Button
                v-if="!job?.has_applied"
                class="gap-2 rounded-xl bg-primary px-6 text-white hover:bg-primary/90 cursor-pointer"
                @click="showApplyModal = true"
              >
                <Send class="h-4 w-4" />
                Apply Now
              </Button>
              <div
                v-else
                class="inline-flex items-center gap-2 rounded-xl bg-secondary/10 px-6 py-2.5 text-sm font-medium text-secondary"
              >
                <CheckCircle2 class="h-4 w-4" />
                Applied
              </div>
            </div>
            <RouterLink
              v-else-if="!isLoggedIn"
              to="/sign-in"
              class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-sm font-medium text-white hover:bg-primary/90"
            >
              Sign in to Apply
            </RouterLink>
          </div>

          <!-- Quick info badges -->
          <div class="mt-5 flex flex-wrap gap-2">
            <span v-if="formatSalary(job.salary_min, job.salary_max)" class="inline-flex items-center gap-1.5 rounded-lg bg-green-50 px-3 py-1.5 text-xs font-medium text-green-700">
              <DollarSign class="h-3.5 w-3.5" />
              {{ formatSalary(job.salary_min, job.salary_max) }}
            </span>
            <span v-if="job.category" class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700">
              {{ job.category.name }}
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-surface-container px-3 py-1.5 text-xs text-on-surface-variant">
              <Clock class="h-3.5 w-3.5" />
              Posted {{ formatDate(job.created_at) }}
            </span>
            <span v-if="job.deadline" class="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700">
              <Calendar class="h-3.5 w-3.5" />
              Deadline: {{ formatDate(job.deadline) }}
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-surface-container px-3 py-1.5 text-xs text-on-surface-variant">
              <Eye class="h-3.5 w-3.5" />
              {{ job.views_count }} views
            </span>
          </div>
        </section>

        <!-- Content -->
        <div class="grid gap-6 lg:grid-cols-[1fr_280px]">
          <div class="space-y-6">
            <!-- Description -->
            <section class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-6 shadow-sm">
              <h2 class="mb-4 text-lg font-semibold text-on-surface">Job Description</h2>
              <div class="whitespace-pre-line text-sm leading-relaxed text-on-surface-variant">{{ job.description }}</div>
            </section>

            <!-- Requirements -->
            <section v-if="job.requirements" class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-6 shadow-sm">
              <h2 class="mb-4 text-lg font-semibold text-on-surface">Requirements</h2>
              <div class="whitespace-pre-line text-sm leading-relaxed text-on-surface-variant">{{ job.requirements }}</div>
            </section>

            <!-- Benefits -->
            <section v-if="job.benefits" class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-6 shadow-sm">
              <h2 class="mb-4 text-lg font-semibold text-on-surface">Benefits</h2>
              <div class="whitespace-pre-line text-sm leading-relaxed text-on-surface-variant">{{ job.benefits }}</div>
            </section>

            <!-- Comments -->
            <section class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-6 shadow-sm">
              <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-on-surface">
                <MessageSquare class="h-5 w-5" />
                Discussion ({{ comments.length }})
              </h2>

              <!-- Comment form -->
              <div v-if="isLoggedIn" class="mb-6">
                <textarea
                  v-model="newComment"
                  rows="3"
                  placeholder="Leave a comment or question..."
                  class="w-full rounded-xl border border-outline-variant bg-surface px-4 py-3 text-sm placeholder:text-on-surface-variant focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                />
                <div class="mt-2 flex justify-end">
                  <Button
                    size="sm"
                    class="rounded-lg"
                    :disabled="!newComment.trim() || commentMutation.isPending.value"
                    @click="commentMutation.mutate()"
                  >
                    {{ commentMutation.isPending.value ? "Posting..." : "Post Comment" }}
                  </Button>
                </div>
              </div>

              <!-- Comments list -->
              <div v-if="comments.length" class="space-y-4">
                <article
                  v-for="comment in comments"
                  :key="comment.id"
                  class="rounded-xl border border-outline-variant/60 bg-surface p-4"
                >
                  <div class="mb-2 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                      <div class="flex h-7 w-7 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary">
                        {{ comment.user?.name?.charAt(0) || "?" }}
                      </div>
                      <span class="text-sm font-medium text-on-surface">{{ comment.user?.name || "User" }}</span>
                      <span class="text-xs text-on-surface-variant">{{ formatDate(comment.created_at) }}</span>
                    </div>
                    <button
                      v-if="isLoggedIn"
                      class="text-on-surface-variant/50 transition-colors hover:text-amber-500"
                      title="Report comment"
                      @click="reportMutation.mutate(comment.id)"
                    >
                      <Flag class="h-3.5 w-3.5" />
                    </button>
                  </div>
                  <p class="text-sm leading-relaxed text-on-surface-variant">{{ comment.content }}</p>
                </article>
              </div>
              <p v-else class="text-center text-sm leading-relaxed text-on-surface-variant">
                No comments yet. Be the first to share your thoughts.
              </p>
            </section>
          </div>

          <!-- Sidebar -->
          <aside class="space-y-4">
            <!-- Company card -->
            <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 shadow-sm">
              <h3 class="mb-3 text-sm font-semibold text-on-surface">About the Company</h3>
              <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 font-bold text-primary">
                  {{ job.employer_profile?.company_name?.charAt(0) || "?" }}
                </div>
                <div>
                  <p class="text-sm font-medium text-on-surface">{{ job.employer_profile?.company_name }}</p>
                  <p v-if="job.employer_profile?.location" class="text-xs leading-relaxed text-on-surface-variant">{{ job.employer_profile.location }}</p>
                </div>
              </div>
              <p v-if="job.employer_profile?.description" class="mt-3 line-clamp-4 text-xs leading-relaxed text-on-surface-variant">
                {{ job.employer_profile.description }}
              </p>
              <a
                v-if="job.employer_profile?.website"
                :href="job.employer_profile.website"
                target="_blank"
                rel="noopener noreferrer"
                class="mt-3 inline-flex items-center gap-1 text-xs text-primary hover:underline"
              >
                <Globe class="h-3 w-3" />
                Visit Website
              </a>
            </div>

            <!-- Apply CTA -->
            <div v-if="isCandidate && !job?.has_applied" class="rounded-2xl border border-primary/20 bg-primary/5 p-5">
              <h3 class="mb-2 text-sm font-semibold text-on-surface">Interested in this role?</h3>
              <p class="mb-3 text-xs leading-relaxed text-on-surface-variant">Quick Apply uses the resume on your profile, or submit a full application with a cover letter.</p>
              <div class="flex flex-col gap-2">
                <Button
                  class="w-full gap-2 rounded-lg cursor-pointer"
                  :disabled="quickApplyMutation.isPending.value"
                  @click="quickApplyMutation.mutate()"
                >
                  <Zap class="h-4 w-4" />
                  {{ quickApplyMutation.isPending.value ? "Applying..." : "Quick Apply" }}
                </Button>
                <Button variant="outline" class="w-full gap-2 rounded-lg border-outline-variant bg-white cursor-pointer" @click="showApplyModal = true">
                  <Send class="h-4 w-4" />
                  Apply with Cover Letter
                </Button>
                <Button variant="outline" class="w-full gap-2 rounded-lg border-outline-variant bg-white cursor-pointer" @click="handleToggleSave">
                  <Bookmark class="h-4 w-4 text-primary" :class="{ 'fill-primary': isSaved }" />
                  {{ isSaved ? 'Saved to Bookmarks' : 'Save Job' }}
                </Button>
              </div>
            </div>
            <div v-else-if="isCandidate && job?.has_applied" class="rounded-2xl border border-secondary/20 bg-secondary/5 p-5">
              <div class="flex items-center gap-2 mb-2">
                <CheckCircle2 class="h-5 w-5 text-secondary" />
                <h3 class="text-sm font-semibold text-on-surface">Application Submitted</h3>
              </div>
              <p class="mb-3 text-xs leading-relaxed text-on-surface-variant">You have already applied for this position. Check your applications for updates.</p>
              <Button variant="outline" class="w-full gap-2 rounded-lg cursor-pointer" @click="handleToggleSave">
                <Bookmark class="h-4 w-4 text-primary" :class="{ 'fill-primary': isSaved }" />
                {{ isSaved ? 'Saved to Bookmarks' : 'Save Job' }}
              </Button>
            </div>
          </aside>
        </div>
      </template>
    </main>

    <Footer />

    <!-- Apply Modal -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-opacity duration-200"
        leave-active-class="transition-opacity duration-150"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="showApplyModal"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
          @click.self="showApplyModal = false"
        >
          <div class="w-full max-w-2xl rounded-2xl border border-outline-variant/20 bg-surface-container-lowest p-8 shadow-xl max-h-[85vh] overflow-y-auto">
            <div class="mb-6 flex items-center justify-between">
              <h2 class="text-xl font-semibold text-on-surface">Apply for {{ job?.title }}</h2>
              <button class="text-on-surface-variant hover:text-on-surface" @click="showApplyModal = false">
                <X class="h-5 w-5" />
              </button>
            </div>

            <div class="space-y-6">
              <!-- Profile summary pre-filled from LinkedIn/profile -->
              <div v-if="prefillName || prefillPhone || prefillLinkedIn" class="rounded-xl border border-outline-variant bg-surface-container-lowest p-4">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-on-surface-variant">From your profile</p>
                <div class="grid gap-2 sm:grid-cols-2">
                  <div v-if="prefillName" class="flex items-center gap-2 text-sm text-on-surface">
                    <span class="font-medium text-on-surface-variant w-16 shrink-0">Name</span>
                    <span>{{ prefillName }}</span>
                  </div>
                  <div v-if="prefillPhone" class="flex items-center gap-2 text-sm text-on-surface">
                    <span class="font-medium text-on-surface-variant w-16 shrink-0">Phone</span>
                    <span>{{ prefillPhone }}</span>
                  </div>
                  <div v-if="prefillLinkedIn" class="col-span-full flex items-center gap-2 text-sm">
                    <span class="font-medium text-on-surface-variant w-16 shrink-0">LinkedIn</span>
                    <a :href="prefillLinkedIn" target="_blank" rel="noopener" class="truncate text-primary hover:underline">{{ prefillLinkedIn }}</a>
                  </div>
                  <div v-if="hasProfileResume" class="col-span-full flex items-center gap-2 text-sm text-on-surface">
                    <span class="font-medium text-on-surface-variant w-16 shrink-0">Resume</span>
                    <span class="text-secondary">Profile resume will be used if you don't upload a new one.</span>
                  </div>
                </div>
              </div>

              <div>
                <label class="mb-2 block text-sm font-medium text-on-surface">Cover Letter <span class="text-on-surface-variant">(optional)</span></label>
                <textarea
                  v-model="coverLetter"
                  rows="7"
                  placeholder="Tell the employer why you're a great fit..."
                  class="w-full resize-y rounded-xl border border-outline-variant bg-surface px-4 py-3.5 text-sm placeholder:text-on-surface-variant focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                />
              </div>

              <div>
                <label class="mb-2 block text-sm font-medium text-on-surface">Resume <span class="text-on-surface-variant">(optional{{ hasProfileResume ? ' – profile resume on file' : '' }})</span></label>
                <div
                  class="relative rounded-xl border-2 border-dashed p-6 text-center transition-colors hover:border-primary/40"
                  :class="resumeFile ? 'border-primary bg-primary/5' : 'border-outline-variant bg-surface'"
                >
                  <Upload class="mx-auto mb-3 h-8 w-8" :class="resumeFile ? 'text-primary' : 'text-on-surface-variant'" />
                  <p class="text-sm" :class="resumeFile ? 'font-medium text-primary' : 'text-on-surface-variant'">
                    {{ resumeFile ? resumeFile.name : "Click to upload PDF, DOCX, or DOC (max 5MB)" }}
                  </p>
                  <input
                    type="file"
                    accept=".pdf,.docx,.doc"
                    class="absolute inset-0 cursor-pointer opacity-0"
                    @change="handleFileChange"
                  />
                </div>
              </div>
            </div>

            <div class="mt-8 flex justify-end gap-4">
              <Button variant="outline" class="rounded-lg px-5 py-2.5" @click="showApplyModal = false">Cancel</Button>
              <Button
                class="rounded-lg gap-2 px-5 py-2.5"
                :disabled="applyMutation.isPending.value"
                @click="applyMutation.mutate()"
              >
                <Send class="h-4 w-4" />
                {{ applyMutation.isPending.value ? "Submitting..." : "Submit Application" }}
              </Button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>
