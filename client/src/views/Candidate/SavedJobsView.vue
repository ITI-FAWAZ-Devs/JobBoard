<script setup lang="ts">
import { computed, ref } from "vue";
import { useQuery, useMutation, useQueryClient } from "@tanstack/vue-query";
import { 
  Bookmark, 
  MapPin, 
  DollarSign, 
  Search, 
  ChevronLeft,
  ChevronRight
} from "lucide-vue-next";
import { useRouter } from "vue-router";
import { Button } from "@/components/ui/button";
import { toast } from "vue-sonner";
import { getSavedJobsApi, unsaveJobApi, type SavedJobItem } from "@/api/jobs";

const page = ref(1);
const searchQuery = ref("");
const selectedFilter = ref<string | null>(null);
const router = useRouter();
const queryClient = useQueryClient();

const { data, isPending, isError } = useQuery({
  queryKey: ["saved-jobs", page],
  queryFn: () => getSavedJobsApi(page.value),
});

const savedJobs = computed<SavedJobItem[]>(() => data.value?.data?.data ?? []);
const meta = computed(() => data.value?.data?.meta ?? null);

// Unsave mutation
const unsaveMutation = useMutation({
  mutationFn: (jobId: number) => unsaveJobApi(jobId),
  onSuccess: () => {
    toast.success("Job removed from saved list.");
    queryClient.invalidateQueries({ queryKey: ["saved-jobs"] });
    // Also invalidate candidate dashboard
    queryClient.invalidateQueries({ queryKey: ["candidate", "dashboard"] });
  },
  onError: () => {
    toast.error("Failed to remove job.");
  },
});

function handleUnsave(jobId: number) {
  unsaveMutation.mutate(jobId);
}

// Client-side filtering/searching for smooth premium UX
const filteredJobs = computed(() => {
  let list = savedJobs.value;

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(item => 
      item.job?.title.toLowerCase().includes(q) || 
      item.job?.employer_profile?.company_name?.toLowerCase().includes(q) ||
      item.job?.description?.toLowerCase().includes(q)
    );
  }

  if (selectedFilter.value) {
    const f = selectedFilter.value.toLowerCase();
    if (f === 'remote') {
      list = list.filter(item => item.job?.location?.toLowerCase().includes('remote'));
    } else if (f === 'full-time') {
      list = list.filter(item => item.job?.work_type?.toLowerCase() === 'full-time');
    } else if (f === 'contract') {
      list = list.filter(item => item.job?.work_type?.toLowerCase() === 'contract');
    } else if (f === 'programming') {
      // Filter by category programming/development/engineering or category names
      list = list.filter(item => 
        item.job?.category?.name?.toLowerCase().includes('programming') ||
        item.job?.category?.name?.toLowerCase().includes('development') ||
        item.job?.category?.name?.toLowerCase().includes('engineering') ||
        item.job?.title?.toLowerCase().includes('developer') ||
        item.job?.title?.toLowerCase().includes('engineer')
      );
    } else if (f === 'design') {
      list = list.filter(item => 
        item.job?.category?.name?.toLowerCase().includes('design') ||
        item.job?.title?.toLowerCase().includes('designer') ||
        item.job?.title?.toLowerCase().includes('ux') ||
        item.job?.title?.toLowerCase().includes('ui')
      );
    }
  }

  return list;
});

function toggleFilter(filter: string) {
  if (selectedFilter.value === filter) {
    selectedFilter.value = null;
  } else {
    selectedFilter.value = filter;
  }
}

function formatSalary(min?: number | null, max?: number | null) {
  if (!min && !max) return null;
  const fmt = (n: number) => n >= 1000 ? `$${(n / 1000).toFixed(0)}k` : `$${n}`;
  if (min && max) return `${fmt(min)} – ${fmt(max)}`;
  if (min) return `From ${fmt(min)}`;
  return `Up to ${fmt(max!)}`;
}
</script>

<template>
  <div class="min-h-screen bg-surface text-on-surface">
    <!-- Top Nav header matching the premium mockup -->
    <header class="h-[72px] sticky top-0 z-40 border-b border-outline-variant bg-surface/95 backdrop-blur-md flex justify-between items-center w-full px-6 md:px-8 shrink-0">
      <!-- Search Input -->
      <div class="flex-1 max-w-md flex items-center">
        <div class="relative w-full">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <Search class="h-5 w-5 text-outline" />
          </div>
          <input 
            v-model="searchQuery"
            class="min-w-50 pl-10 pr-4 py-2 bg-surface-container-lowest border border-outline-variant rounded-full text-body-md font-body-md text-on-surface placeholder:text-outline focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all shadow-sm" 
            placeholder="Search saved jobs..." 
            type="text"
          />
        </div>
      </div>

      <!-- Quick Action -->
      <div class="flex items-center gap-4 ml-auto">
        <Button variant="outline" size="sm" class="rounded-full hidden sm:inline-flex" @click="router.push('/jobs')">
          Browse All Jobs
        </Button>
      </div>
    </header>

    <!-- Main Canvas -->
    <main class="flex-1 p-4 md:p-8 w-full max-w-container-max mx-auto">
      <!-- Header Section -->
      <div class="mb-8">
        <h2 class="font-headline-lg text-headline-lg text-on-surface mb-2">Saved Jobs</h2>
        <p class="font-body-md text-body-md text-on-surface-variant mb-6">
          You have {{ meta?.total ?? 0 }} jobs saved. Keep track of opportunities you're interested in.
        </p>

        <!-- Filter Chips -->
        <div class="flex flex-wrap gap-2">
          <button 
            v-for="chip in ['Remote', 'Full-time', 'Contract', 'Programming', 'Design']"
            :key="chip"
            @click="toggleFilter(chip)"
            class="px-4 py-1.5 rounded-full border text-label-sm font-label-sm transition-all duration-200"
            :class="selectedFilter === chip 
              ? 'bg-primary border-primary text-white font-semibold' 
              : 'border-outline-variant bg-surface-container-lowest text-on-surface hover:bg-surface-container-low hover:border-outline'"
          >
            {{ chip }}
          </button>
        </div>
      </div>

      <!-- Loading / Error states -->
      <div v-if="isPending" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div v-for="i in 4" :key="i" class="animate-pulse rounded-xl border border-outline-variant bg-surface-container-lowest p-6 h-[220px]" />
      </div>

      <div v-else-if="isError" class="rounded-xl border border-outline-variant bg-surface-container-lowest p-12 text-center">
        <p class="text-on-surface-variant">Failed to load saved jobs. Please try again.</p>
        <Button class="mt-4" @click="queryClient.invalidateQueries({ queryKey: ['saved-jobs'] })">Retry</Button>
      </div>

      <!-- Empty state -->
      <div v-else-if="!filteredJobs.length" class="rounded-xl border border-outline-variant bg-surface-container-lowest p-12 text-center">
        <Bookmark class="mx-auto mb-3 h-12 w-12 text-outline-variant" />
        <h3 class="mb-1 text-lg font-semibold text-on-surface">No saved jobs found</h3>
        <p class="text-sm text-on-surface-variant max-w-sm mx-auto">
          {{ searchQuery || selectedFilter ? 'Try clearing your filters or search query.' : 'Bookmark jobs while searching, and they will show up here.' }}
        </p>
        <Button v-if="searchQuery || selectedFilter" variant="outline" class="mt-4" @click="searchQuery = ''; selectedFilter = null">
          Clear Filters
        </Button>
        <Button v-else class="mt-4" @click="router.push('/jobs')">
          Browse Jobs
        </Button>
      </div>

      <!-- Job Grid -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <article 
          v-for="item in filteredJobs" 
          :key="item.id"
          class="bg-surface-container-lowest rounded-xl p-md shadow-soft-lift flex flex-col md:flex-row gap-6 border border-surface-container hover:shadow-md transition-shadow duration-300 relative group"
        >
          <!-- Bookmark Action -->
          <button 
            @click="handleUnsave(item.job.id)"
            aria-label="Remove saved job" 
            class="absolute top-4 right-4 text-primary p-1 hover:bg-surface-container-low rounded-full transition-colors"
          >
            <Bookmark class="h-5 w-5 fill-primary" />
          </button>

          <!-- Company Avatar -->
          <div class="w-16 h-16 rounded-lg bg-surface-container-low border border-outline-variant flex items-center justify-center shrink-0 overflow-hidden font-headline-md font-bold text-primary">
            {{ item.job.employer_profile?.company_name?.charAt(0) || '?' }}
          </div>

          <div class="flex-1 flex flex-col justify-between">
            <div>
              <h3 class="font-headline-sm text-headline-sm text-on-surface mb-1 pr-8">
                {{ item.job.title }}
              </h3>
              <p class="font-body-sm text-body-sm text-on-surface-variant font-medium mb-3">
                {{ item.job.employer_profile?.company_name || 'Unknown Company' }}
              </p>

              <div class="flex flex-wrap gap-x-4 gap-y-2 mb-4">
                <div class="flex items-center gap-1 text-on-surface-variant font-label-sm text-label-sm">
                  <MapPin class="h-4 w-4 text-outline" />
                  {{ item.job.location || 'Remote' }}
                </div>
                <div class="flex items-center gap-1 text-on-surface-variant font-label-sm text-label-sm">
                  <DollarSign class="h-4 w-4 text-outline" />
                  {{ formatSalary(item.job.salary_min, item.job.salary_max) || 'N/A' }}
                </div>
              </div>

              <div class="flex flex-wrap gap-2 mb-6">
                <span 
                  v-if="item.job.category"
                  class="px-2.5 py-1 rounded-full bg-[#E0F2FE] text-[#0284C7] font-label-sm text-[11px] uppercase tracking-wider font-semibold"
                >
                  {{ item.job.category.name }}
                </span>
                <span 
                  class="px-2.5 py-1 rounded-full bg-[#CCFBF1] text-[#0F766E] font-label-sm text-[11px] uppercase tracking-wider font-semibold capitalize"
                >
                  {{ item.job.work_type }}
                </span>
              </div>
            </div>

            <!-- Footer Details -->
            <div class="flex items-center justify-between border-t border-outline-variant pt-4 mt-auto">
              <span class="font-body-sm text-body-sm text-outline">
                Saved {{ item.saved_at }}
              </span>
              <div class="flex gap-3">
                <button 
                  @click="handleUnsave(item.job.id)"
                  class="font-label-md text-label-md text-on-surface-variant hover:text-error px-4 py-2 rounded-lg transition-colors border border-transparent hover:border-error-container"
                >
                  Remove
                </button>
                <Button 
                  @click="router.push(`/jobs/${item.job.id}`)"
                  class="bg-primary text-on-primary font-label-md text-label-md px-6 py-2 rounded-lg hover:bg-opacity-90 transition-colors shadow-sm cursor-pointer"
                >
                  Apply Now
                </Button>
              </div>
            </div>
          </div>
        </article>
      </div>

      <!-- Pagination -->
      <div v-if="meta && meta.last_page > 1" class="mt-8 flex items-center justify-center gap-2">
        <Button 
          variant="outline" 
          size="sm" 
          :disabled="page <= 1" 
          @click="page--"
        >
          <ChevronLeft class="h-4 w-4" />
          Previous
        </Button>
        <span class="px-3 text-sm text-on-surface-variant">
          Page {{ meta.current_page }} of {{ meta.last_page }}
        </span>
        <Button 
          variant="outline" 
          size="sm" 
          :disabled="page >= meta.last_page" 
          @click="page++"
        >
          Next
          <ChevronRight class="h-4 w-4" />
        </Button>
      </div>
    </main>
  </div>
</template>

<style scoped>
.shadow-soft-lift {
  box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.05);
}
</style>
