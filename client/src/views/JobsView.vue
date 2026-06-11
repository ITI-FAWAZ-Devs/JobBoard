<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useQuery, useMutation, useQueryClient } from "@tanstack/vue-query";
import { RouterLink, useRouter } from "vue-router";
import {
  Briefcase,
  CheckCircle2,
  MapPin,
  Search,
  ChevronLeft,
  ChevronRight,
  X,
  Building2,
  Bookmark,
} from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import Navbar from "@/components/shared/Navbar.vue";
import Footer from "@/components/shared/Footer.vue";
import { 
  getJobsApi, 
  getCategoriesApi, 
  getSavedJobsApi,
  saveJobApi,
  unsaveJobApi,
  type JobListingPublic, 
  type JobFilters 
} from "@/api/jobs";
import { toast } from "vue-sonner";

const searchVal = ref("");
const searchQuery = ref("");
const selectedCategory = ref<number | null>(null);
const selectedWorkType = ref("");
const selectedLocation = ref("");
const salaryMin = ref<number | undefined>();
const salaryMax = ref<number | undefined>();
const page = ref(1);
const sortBy = ref("Relevance");

const workTypes = [
  { label: "Remote", value: "remote" },
  { label: "Hybrid", value: "hybrid" },
  { label: "Onsite", value: "onsite" },
  { label: "Full-time", value: "full-time" },
  { label: "Part-time", value: "part-time" },
  { label: "Contract", value: "contract" },
  { label: "Freelance", value: "freelance" },
];

const filters = computed<JobFilters>(() => ({
  q: searchQuery.value || undefined,
  category_id: selectedCategory.value || undefined,
  work_type: selectedWorkType.value || undefined,
  location: selectedLocation.value || undefined,
  salary_min: salaryMin.value || undefined,
  salary_max: salaryMax.value || undefined,
  page: page.value,
  per_page: 12,
}));

const { data: categoriesData } = useQuery({
  queryKey: ["categories"],
  queryFn: getCategoriesApi,
});

const categories = computed(() => categoriesData.value?.data ?? []);

const { data, isPending, isError } = useQuery({
  queryKey: ["jobs", filters],
  queryFn: () => getJobsApi(filters.value),
});

const jobs = computed<JobListingPublic[]>(() => data.value?.data ?? []);
const meta = computed(() => data.value?.meta ?? null);

const sortedJobs = computed(() => {
  const list = [...jobs.value];
  if (sortBy.value === "Highest Salary") {
    return list.sort((a, b) => Number(b.salary_max ?? 0) - Number(a.salary_max ?? 0));
  }
  if (sortBy.value === "Most Recent") {
    return list.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());
  }
  return list;
});

const hasActiveFilters = computed(
  () =>
    !!selectedCategory.value ||
    !!selectedWorkType.value ||
    !!selectedLocation.value ||
    !!salaryMin.value ||
    !!salaryMax.value ||
    !!searchQuery.value
);

function clearFilters() {
  selectedCategory.value = null;
  selectedWorkType.value = "";
  selectedLocation.value = "";
  salaryMin.value = undefined;
  salaryMax.value = undefined;
  searchVal.value = "";
  searchQuery.value = "";
  page.value = 1;
}

function triggerSearch() {
  searchQuery.value = searchVal.value;
}

function formatSalary(min?: number | null, max?: number | null) {
  if (!min && !max) return null;
  const fmt = (n: number) =>
    n >= 1000 ? `$${(n / 1000).toFixed(0)}k` : `$${n}`;
  if (min && max) return `${fmt(min)} – ${fmt(max)}`;
  if (min) return `From ${fmt(min)}`;
  return `Up to ${fmt(max!)}`;
}

const router = useRouter();
const queryClient = useQueryClient();

const isLoggedIn = computed(() => Boolean(localStorage.getItem("token")));
const storedUser = computed(() => {
  try {
    return JSON.parse(localStorage.getItem("user") || "null");
  } catch {
    return null;
  }
});
const isCandidate = computed(() => storedUser.value?.role === "candidate");

const { data: savedData } = useQuery({
  queryKey: ["saved-jobs", 1],
  queryFn: () => getSavedJobsApi(1),
  enabled: isCandidate,
});

const savedJobIds = computed(() => new Set((savedData.value?.data?.data ?? []).map(item => item.job?.id)));

const toggleSaveMutation = useMutation({
  mutationFn: async ({ jobId, isSaved }: { jobId: number; isSaved: boolean }) => {
    if (isSaved) {
      await unsaveJobApi(jobId);
    } else {
      await saveJobApi(jobId);
    }
  },
  onSuccess: (_, variables) => {
    toast.success(variables.isSaved ? "Job removed from saved list." : "Job saved successfully.");
    queryClient.invalidateQueries({ queryKey: ["saved-jobs"] });
    queryClient.invalidateQueries({ queryKey: ["candidate", "dashboard"] });
  },
  onError: () => {
    toast.error("Failed to update saved status.");
  },
});

function handleToggleSave(jobId: number, event: Event) {
  event.preventDefault();
  event.stopPropagation();
  if (!isLoggedIn.value) {
    router.push("/sign-in");
    return;
  }
  const isSaved = savedJobIds.value.has(jobId);
  toggleSaveMutation.mutate({ jobId, isSaved });
}

watch([searchQuery, selectedCategory, selectedWorkType, selectedLocation, salaryMin, salaryMax], () => {
  page.value = 1;
});
</script>

<template>
  <div class="min-h-screen bg-surface text-on-surface antialiased flex flex-col">
    <Navbar />

    <!-- Main Content Canvas -->
    <main class="flex-grow w-full max-w-container-max mx-auto px-md py-lg flex flex-col md:flex-row gap-gutter">
      
      <!-- Left Sidebar: Filters -->
      <aside class="w-full md:w-64 flex-shrink-0 flex flex-col gap-lg">
        <div class="flex items-center justify-between">
          <h2 class="font-headline-sm text-headline-sm text-on-surface">Filters</h2>
          <button @click="clearFilters" class="font-label-sm text-label-sm text-primary hover:underline cursor-pointer">
            Clear All
          </button>
        </div>
        
        <!-- Filter Card -->
        <div class="bg-surface-container-lowest rounded-lg p-md ambient-shadow flex flex-col gap-md">
          <!-- Category Filter -->
          <div class="flex flex-col gap-sm">
            <h3 class="font-label-md text-label-md text-on-surface">Category</h3>
            <div class="flex flex-col gap-xs">
              <label 
                v-for="cat in categories" 
                :key="cat.id" 
                class="flex items-center gap-xs cursor-pointer group"
              >
                <input 
                  type="checkbox" 
                  :checked="selectedCategory === cat.id"
                  @change="selectedCategory = selectedCategory === cat.id ? null : cat.id"
                  class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary cursor-pointer animate-none"
                />
                <span class="font-body-sm text-body-sm text-on-surface-variant group-hover:text-on-surface transition-colors">
                  {{ cat.name }}
                </span>
              </label>
            </div>
          </div>
          
          <hr class="border-t border-outline-variant opacity-50">
          
          <!-- Location Filter -->
          <div class="flex flex-col gap-sm">
            <h3 class="font-label-md text-label-md text-on-surface">Location</h3>
            <div class="relative">
              <MapPin class="absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant h-5 w-5 pointer-events-none" />
              <input 
                v-model="selectedLocation"
                class="w-full pl-10 pr-3 py-1.5 rounded-md border border-outline-variant bg-surface-container-lowest font-body-sm text-body-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all placeholder:text-outline" 
                placeholder="City or Country" 
                type="text"
              />
            </div>
          </div>
          
          <hr class="border-t border-outline-variant opacity-50">
          
          <!-- Work Type Filter -->
          <div class="flex flex-col gap-sm">
            <h3 class="font-label-md text-label-md text-on-surface">Work Type</h3>
            <div class="flex flex-col gap-xs">
              <label 
                v-for="wt in workTypes" 
                :key="wt.value" 
                class="flex items-center gap-xs cursor-pointer group"
              >
                <input 
                  type="checkbox" 
                  :checked="selectedWorkType === wt.value"
                  @change="selectedWorkType = selectedWorkType === wt.value ? '' : wt.value"
                  class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary cursor-pointer animate-none"
                />
                <span class="font-body-sm text-body-sm text-on-surface-variant group-hover:text-on-surface capitalize transition-colors">
                  {{ wt.label }}
                </span>
              </label>
            </div>
          </div>
          
          <hr class="border-t border-outline-variant opacity-50">
          
          <!-- Salary Range -->
          <div class="flex flex-col gap-sm">
            <h3 class="font-label-md text-label-md text-on-surface">Salary Range (Annually)</h3>
            <div class="flex items-center gap-sm">
              <div class="flex-1 relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant font-body-sm pointer-events-none">$</span>
                <input 
                  v-model.number="salaryMin"
                  class="w-full pl-7 pr-2 py-1 rounded-md border border-outline-variant bg-surface-container-lowest font-body-sm text-body-sm focus:border-primary outline-none" 
                  placeholder="Min" 
                  type="number"
                />
              </div>
              <span class="text-on-surface-variant font-body-sm">-</span>
              <div class="flex-1 relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant font-body-sm pointer-events-none">$</span>
                <input 
                  v-model.number="salaryMax"
                  class="w-full pl-7 pr-2 py-1 rounded-md border border-outline-variant bg-surface-container-lowest font-body-sm text-body-sm focus:border-primary outline-none" 
                  placeholder="Max" 
                  type="number"
                />
              </div>
            </div>
          </div>
        </div>
      </aside>

      <!-- Main Content Area: Search & Job Grid -->
      <section class="flex-grow flex flex-col gap-lg min-w-0">
        <!-- Top Search Bar -->
        <div class="bg-surface-container-lowest rounded-lg p-sm ambient-shadow flex flex-col md:flex-row gap-sm items-center border border-outline-variant/20">
          <div class="flex-grow flex items-center bg-surface px-md py-xs rounded-md border border-outline-variant focus-within:border-primary focus-within:ring-1 focus-within:ring-primary w-full transition-all">
            <Search class="text-on-surface-variant mr-3 h-5 w-5 pointer-events-none" />
            <input 
              v-model="searchVal"
              @keyup.enter="triggerSearch"
              class="w-full bg-transparent border-none outline-none font-body-md text-body-md text-on-surface placeholder:text-outline p-0 focus:ring-0" 
              placeholder="Search job title, keywords, or company" 
              type="text"
            />
          </div>
          <button 
            @click="triggerSearch"
            class="bg-primary-container text-on-primary-container font-label-md text-label-md px-xl py-sm rounded-lg hover:opacity-90 transition-opacity w-full md:w-auto flex-shrink-0 cursor-pointer"
          >
            Search Jobs
          </button>
        </div>

        <!-- Results Header -->
        <div class="flex justify-between items-end">
          <div>
            <h1 class="font-headline-md text-headline-md text-on-surface">Recommended Jobs</h1>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">
              Showing {{ meta?.total ?? 0 }} jobs based on your profile
            </p>
          </div>
          <div class="flex items-center gap-xs">
            <span class="font-label-sm text-label-sm text-on-surface-variant">Sort by:</span>
            <select 
              v-model="sortBy"
              class="font-label-sm text-label-sm bg-transparent border-none text-on-surface focus:ring-0 cursor-pointer outline-none"
            >
              <option value="Relevance">Relevance</option>
              <option value="Most Recent">Most Recent</option>
              <option value="Highest Salary">Highest Salary</option>
            </select>
          </div>
        </div>

        <!-- Loading state -->
        <div v-if="isPending" class="grid grid-cols-1 xl:grid-cols-2 gap-md">
          <div v-for="i in 4" :key="i" class="animate-pulse rounded-lg p-md ambient-shadow border border-outline-variant/30 h-[220px] bg-surface-container-lowest" />
        </div>

        <!-- Error state -->
        <div v-else-if="isError" class="rounded-xl border border-outline-variant bg-surface-container-lowest p-8 text-center">
          <p class="text-sm leading-relaxed text-on-surface-variant">Failed to load jobs. Please try again.</p>
        </div>

        <!-- Empty state -->
        <div v-else-if="!sortedJobs.length" class="rounded-xl border border-outline-variant bg-surface-container-lowest p-12 text-center">
          <Briefcase class="mx-auto mb-3 h-12 w-12 text-on-surface-variant/40" />
          <h3 class="mb-1 text-lg font-semibold text-on-surface">No jobs found</h3>
          <p class="text-sm leading-relaxed text-on-surface-variant">
            Try adjusting your search or filters to find what you're looking for.
          </p>
          <Button variant="outline" class="mt-4" @click="clearFilters">Clear Filters</Button>
        </div>

        <!-- Job Cards Grid -->
        <div v-else class="grid grid-cols-1 xl:grid-cols-2 gap-md">
          <div 
            v-for="job in sortedJobs" 
            :key="job.id"
            class="bg-surface-container-lowest rounded-lg p-md ambient-shadow border border-outline-variant/30 flex flex-col gap-md relative group hover:shadow-lg transition-all duration-300 hover:-translate-y-1"
          >
            <div class="flex justify-between items-start">
              <div class="flex items-center gap-sm">
                <!-- Company Logo / Initials -->
                <div class="w-12 h-12 rounded bg-surface-variant overflow-hidden flex-shrink-0 border border-outline-variant/20 flex items-center justify-center font-bold text-primary text-lg">
                  {{ job.employer_profile?.company_name?.charAt(0) || "?" }}
                </div>
                <div>
                  <h3 
                    @click="router.push(`/jobs/${job.id}`)"
                    class="font-headline-sm text-headline-sm text-on-surface line-clamp-1 group-hover:text-primary transition-colors cursor-pointer"
                  >
                    {{ job.title }}
                  </h3>
                  <p class="font-body-sm text-body-sm leading-relaxed text-on-surface-variant">
                    {{ job.employer_profile?.company_name || "Company" }} • {{ job.location || "Remote" }}
                  </p>
                </div>
              </div>

              <!-- Bookmark/Save Button -->
              <button 
                v-if="isCandidate"
                @click="handleToggleSave(job.id, $event)"
                class="text-on-surface-variant hover:text-primary transition-colors flex items-center justify-center p-1 rounded-full hover:bg-surface-variant cursor-pointer"
                :title="savedJobIds.has(job.id) ? 'Remove from saved' : 'Save job'"
              >
                <Bookmark class="h-5 w-5" :class="{ 'fill-primary text-primary': savedJobIds.has(job.id) }" />
              </button>
            </div>

            <!-- Tags -->
            <div class="flex flex-wrap gap-xs">
              <span class="bg-surface-variant text-on-surface px-3 py-[2px] rounded-full font-label-sm text-label-sm capitalize">
                {{ job.work_type }}
              </span>
              <span v-if="job.category" class="bg-surface-variant text-on-surface px-3 py-[2px] rounded-full font-label-sm text-label-sm">
                {{ job.category.name }}
              </span>
            </div>

            <!-- Description -->
            <p class="font-body-sm text-body-sm text-on-surface-variant line-clamp-2 leading-relaxed">
              {{ job.description }}
            </p>

            <!-- Footer / Salary & Action -->
            <div class="mt-auto pt-sm flex justify-between items-center border-t border-outline-variant/30">
              <div class="font-label-md text-label-md text-on-surface">
                {{ formatSalary(job.salary_min, job.salary_max) || "N/A" }}
                <span v-if="job.salary_min || job.salary_max" class="font-body-sm text-body-sm text-on-surface-variant font-normal">/yr</span>
              </div>
              <button
                v-if="!job.has_applied"
                @click="router.push(`/jobs/${job.id}`)"
                class="bg-primary-container text-on-primary-container font-label-md text-label-md px-md py-xs rounded-lg hover:opacity-90 transition-opacity cursor-pointer shadow-sm"
              >
                Apply Now
              </button>
              <span
                v-else
                class="inline-flex items-center gap-1 rounded-lg bg-secondary/10 px-md py-xs font-label-md text-label-md text-secondary"
              >
                <CheckCircle2 class="h-3.5 w-3.5" />
                Applied
              </span>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="meta && meta.last_page > 1" class="flex justify-center mt-md gap-xs">
          <button 
            :disabled="page <= 1"
            @click="page--"
            class="w-8 h-8 flex items-center justify-center rounded border border-outline-variant text-on-surface-variant hover:bg-surface-variant transition-colors disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
          >
            <ChevronLeft class="h-4 w-4" />
          </button>
          
          <button 
            v-for="p in meta.last_page" 
            :key="p"
            @click="page = p"
            class="w-8 h-8 flex items-center justify-center rounded font-label-md text-label-md transition-colors cursor-pointer"
            :class="page === p 
              ? 'bg-primary text-on-primary font-bold' 
              : 'border border-outline-variant text-on-surface-variant hover:bg-surface-variant'"
          >
            {{ p }}
          </button>
          
          <button 
            :disabled="page >= meta.last_page"
            @click="page++"
            class="w-8 h-8 flex items-center justify-center rounded border border-outline-variant text-on-surface-variant hover:bg-surface-variant transition-colors disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
          >
            <ChevronRight class="h-4 w-4" />
          </button>
        </div>
      </section>
    </main>

    <Footer />
  </div>
</template>

<style scoped>
.ambient-shadow {
  box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.05);
}
</style>
