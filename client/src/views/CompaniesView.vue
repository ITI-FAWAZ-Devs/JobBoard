<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useQuery } from "@tanstack/vue-query";
import { useRouter } from "vue-router";
import { 
  Search, 
  MapPin, 
  Users, 
  Briefcase, 
  ArrowRight,
  ChevronLeft,
  ChevronRight,
  Building2,
  Bookmark
} from "lucide-vue-next";
import Navbar from "@/components/shared/Navbar.vue";
import Footer from "@/components/shared/Footer.vue";
import { Button } from "@/components/ui/button";
import { getCompaniesApi, type CompanyProfile } from "@/api/jobs";
import { toast } from "vue-sonner";

const searchVal = ref("");
const searchQuery = ref("");
const page = ref(1);
const sortBy = ref("Most Relevant");

// Filter arrays
const selectedIndustries = ref<string[]>([]);
const selectedSizes = ref<string[]>([]);
const selectedLocations = ref<string[]>([]);
const locationSearch = ref("");

const industriesList = [
  { label: "Programming", count: 142 },
  { label: "Marketing", count: 85 },
  { label: "Finance", count: 64 },
  { label: "Design", count: 43 },
  { label: "Sales", count: 112 },
];

const sizesList = [
  { label: "1-10 Employees", value: "1-10" },
  { label: "11-50 Employees", value: "11-50" },
  { label: "51-200 Employees", value: "51-200" },
  { label: "201-500 Employees", value: "201-500" },
  { label: "501+ Employees", value: "501+" },
];

const locationsList = [
  { label: "Remote", value: "Remote", count: 320 },
  { label: "San Francisco, CA", value: "San Francisco, CA", count: 85 },
  { label: "New York, NY", value: "New York, NY", count: 72 },
];

const router = useRouter();

const companyFilters = computed(() => {
  // Convert sorting
  let sort = "relevance";
  if (sortBy.value === "Most Open Jobs") sort = "most_open_jobs";
  if (sortBy.value === "Alphabetical") sort = "alphabetical";

  return {
    q: searchQuery.value || undefined,
    industry: selectedIndustries.value.length ? selectedIndustries.value : undefined,
    employee_count: selectedSizes.value.length ? selectedSizes.value : undefined,
    location: selectedLocations.value.length ? selectedLocations.value : undefined,
    sort,
    page: page.value,
  };
});

const { data: apiData, isPending, isError } = useQuery({
  queryKey: ["companies", companyFilters],
  queryFn: () => getCompaniesApi(companyFilters.value),
});

const companiesData = computed(() => apiData.value?.data ?? null);
const companies = computed<CompanyProfile[]>(() => (companiesData.value as any)?.data ?? []);
const meta = computed(() => (companiesData.value as any)?.meta ?? null);

// Popular Companies (the top 3 with the most open roles from the overall list)
const { data: popularData } = useQuery({
  queryKey: ["popular-companies"],
  queryFn: () => getCompaniesApi({ sort: "most_open_jobs" }),
});
const popularCompanies = computed<CompanyProfile[]>(() => 
  ((popularData.value?.data as any)?.data ?? []).slice(0, 3)
);

// Search and location filtering
const filteredLocationsList = computed(() => {
  if (!locationSearch.value.trim()) return locationsList;
  const q = locationSearch.value.toLowerCase();
  return locationsList.filter(loc => loc.label.toLowerCase().includes(q));
});

function toggleIndustry(ind: string) {
  const idx = selectedIndustries.value.indexOf(ind);
  if (idx > -1) {
    selectedIndustries.value.splice(idx, 1);
  } else {
    selectedIndustries.value.push(ind);
  }
  page.value = 1;
}

function toggleSize(size: string) {
  const idx = selectedSizes.value.indexOf(size);
  if (idx > -1) {
    selectedSizes.value.splice(idx, 1);
  } else {
    selectedSizes.value.push(size);
  }
  page.value = 1;
}

function toggleLocation(loc: string) {
  const idx = selectedLocations.value.indexOf(loc);
  if (idx > -1) {
    selectedLocations.value.splice(idx, 1);
  } else {
    selectedLocations.value.push(loc);
  }
  page.value = 1;
}

function clearAllFilters() {
  selectedIndustries.value = [];
  selectedSizes.value = [];
  selectedLocations.value = [];
  searchVal.value = "";
  searchQuery.value = "";
  locationSearch.value = "";
  sortBy.value = "Most Relevant";
  page.value = 1;
}

function triggerSearch() {
  searchQuery.value = searchVal.value;
  page.value = 1;
}

// Follow toggle states (local mock state for visual premium follow behavior)
const followedCompanies = ref<Set<number>>(new Set());
function toggleFollow(companyId: number) {
  if (followedCompanies.value.has(companyId)) {
    followedCompanies.value.delete(companyId);
    toast.success("Unfollowed company.");
  } else {
    followedCompanies.value.add(companyId);
    toast.success("Following company.");
  }
}

function openWebsite(url?: string | null) {
  if (url) {
    window.open(url, "_blank", "noopener,noreferrer");
  }
}
</script>

<template>
  <div class="bg-background text-on-background antialiased min-h-screen flex flex-col">
    <Navbar />

    <!-- Main Content -->
    <main class="flex-grow w-full max-w-container-max mx-auto px-sm md:px-gutter py-xl">
      <!-- Page Header & Global Search -->
      <div class="mb-xl text-center max-w-2xl mx-auto">
        <h1 class="font-headline-xl text-headline-xl text-on-background mb-xs">Browse Companies</h1>
        <p class="font-body-lg text-body-lg leading-relaxed text-on-surface-variant mb-lg">
          Discover top employers and find your future workplace. Explore company cultures, benefits, and open roles.
        </p>
        <div class="relative w-full max-w-xl mx-auto custom-shadow-soft rounded-full bg-surface-container-lowest flex items-center p-2 border border-outline-variant focus-within:border-primary focus-within:ring-1 focus-within:ring-primary transition-all">
          <Search class="text-outline ml-3 h-5 w-5 pointer-events-none" />
          <input 
            v-model="searchVal"
            @keyup.enter="triggerSearch"
            class="flex-grow bg-transparent border-none focus:ring-0 text-body-md font-body-md text-on-surface px-sm outline-none placeholder:text-outline w-full" 
            placeholder="Search companies by name, industry, or keyword..." 
            type="text"
          />
          <button 
            @click="triggerSearch"
            class="bg-primary text-on-primary font-label-md text-label-md px-lg py-2 rounded-full hover:bg-primary-container transition-colors flex-shrink-0 cursor-pointer"
          >
            Search
          </button>
        </div>
      </div>

      <!-- Featured/Popular Companies -->
      <section v-if="popularCompanies.length" class="mb-xl">
        <div class="flex justify-between items-end mb-md">
          <h2 class="font-headline-md text-headline-md text-on-background">Popular Companies</h2>
          <button @click="sortBy = 'Most Open Jobs'" class="font-label-md text-label-md text-primary hover:underline cursor-pointer bg-transparent border-none">
            View all trending
          </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
          <!-- Featured Card -->
          <div 
            v-for="(company, index) in popularCompanies"
            :key="company.id"
            @click="openWebsite(company.website)"
            class="bg-surface-container-lowest rounded-xl p-md custom-shadow-soft hover:custom-shadow-hover transition-shadow duration-300 border border-outline-variant flex flex-col group cursor-pointer relative overflow-hidden"
          >
            <div 
              :class="[
                'absolute top-0 right-0 w-32 h-32 rounded-bl-full -mr-8 -mt-8 z-0 transition-transform group-hover:scale-110',
                index === 0 ? 'bg-primary/5' : index === 1 ? 'bg-secondary/5' : 'bg-tertiary/5'
              ]"
            ></div>
            <div class="relative z-10 flex items-start gap-sm mb-sm">
              <div class="w-16 h-16 rounded-lg bg-surface border border-outline-variant p-2 flex-shrink-0 flex items-center justify-center">
                <img v-if="company.logo_url" :src="company.logo_url" :alt="company.company_name" class="w-full h-full object-contain" />
                <span 
                  v-else 
                  :class="[
                    'font-bold text-xl uppercase',
                    index === 0 ? 'text-primary' : index === 1 ? 'text-secondary' : 'text-tertiary'
                  ]"
                >
                  {{ company.company_name?.charAt(0) || '?' }}
                </span>
              </div>
              <div>
                <h3 class="font-headline-sm text-headline-sm text-on-background group-hover:text-primary transition-colors">
                  {{ company.company_name }}
                </h3>
                <p class="font-body-sm text-body-sm text-on-surface-variant flex items-center gap-1">
                  <Briefcase class="h-4 w-4 text-on-surface-variant" />
                  {{ company.industry || 'Tech Company' }}
                </p>
              </div>
            </div>
            <p class="font-body-sm text-body-sm text-on-surface mb-md flex-grow line-clamp-2 leading-relaxed">
              {{ company.description || 'No description available for this company.' }}
            </p>
            <div class="flex items-center justify-between mt-auto pt-md border-t border-outline-variant/50 relative z-10">
              <div 
                @click.stop="router.push(`/jobs?q=${company.company_name}`)"
                class="flex items-center gap-xs text-secondary font-label-sm text-label-sm bg-secondary-container/20 px-2 py-1 rounded-full hover:bg-secondary-container/40 transition-colors cursor-pointer"
              >
                <Briefcase class="h-3.5 w-3.5 text-secondary" />
                <span>{{ company.open_jobs_count }} Open Roles</span>
              </div>
              <span class="font-label-sm text-label-sm text-primary group-hover:underline flex items-center gap-1">
                View Site <ArrowRight class="h-3.5 w-3.5" />
              </span>
            </div>
          </div>
        </div>
      </section>

      <!-- Main Layout: Sidebar & Grid -->
      <div class="flex flex-col lg:flex-row gap-lg">
        <!-- Left Sidebar: Filters -->
        <aside class="w-full lg:w-64 flex-shrink-0 space-y-md">
          <div class="bg-surface-container-lowest rounded-xl p-md border border-outline-variant custom-shadow-soft">
            <div class="flex items-center justify-between mb-md">
              <h3 class="font-headline-sm text-headline-sm text-on-background">Filters</h3>
              <button @click="clearAllFilters" class="font-label-sm text-label-sm text-outline hover:text-primary cursor-pointer bg-transparent border-none">
                Clear All
              </button>
            </div>
            
            <!-- Industry Filter -->
            <div class="mb-md">
              <h4 class="font-label-md text-label-md text-on-background mb-xs">Industry</h4>
              <div class="space-y-2">
                <label 
                  v-for="ind in industriesList" 
                  :key="ind.label" 
                  class="flex items-center gap-2 cursor-pointer group"
                >
                  <input 
                    type="checkbox"
                    :checked="selectedIndustries.includes(ind.label)"
                    @change="toggleIndustry(ind.label)"
                    class="rounded border-outline text-primary focus:ring-primary h-4 w-4"
                  />
                  <span class="font-body-sm text-body-sm text-on-surface-variant group-hover:text-on-surface">
                    {{ ind.label }} ({{ ind.count }})
                  </span>
                </label>
              </div>
            </div>
            
            <hr class="border-outline-variant my-md"/>
            
            <!-- Company Size Filter -->
            <div class="mb-md">
              <h4 class="font-label-md text-label-md text-on-background mb-xs">Company Size</h4>
              <div class="space-y-2">
                <label 
                  v-for="size in sizesList" 
                  :key="size.value" 
                  class="flex items-center gap-2 cursor-pointer group"
                >
                  <input 
                    type="checkbox"
                    :checked="selectedSizes.includes(size.value)"
                    @change="toggleSize(size.value)"
                    class="rounded border-outline text-primary focus:ring-primary h-4 w-4"
                  />
                  <span class="font-body-sm text-body-sm text-on-surface-variant group-hover:text-on-surface">
                    {{ size.label }}
                  </span>
                </label>
              </div>
            </div>
            
            <hr class="border-outline-variant my-md"/>
            
            <!-- Location Filter -->
            <div>
              <h4 class="font-label-md text-label-md text-on-background mb-xs">Location</h4>
              <div class="relative w-full mb-2">
                <Search class="absolute left-2 top-1/2 transform -translate-y-1/2 text-outline h-4 w-4 pointer-events-none" />
                <input 
                  v-model="locationSearch"
                  class="w-full pl-8 pr-2 py-1.5 bg-surface rounded-md border border-outline-variant focus:ring-1 focus:ring-primary text-body-sm font-body-sm text-on-surface outline-none transition-all" 
                  placeholder="Search city..." 
                  type="text"
                />
              </div>
              <div class="space-y-2 mt-2 max-h-32 overflow-y-auto pr-1">
                <label 
                  v-for="loc in filteredLocationsList" 
                  :key="loc.value" 
                  class="flex items-center gap-2 cursor-pointer group"
                >
                  <input 
                    type="checkbox"
                    :checked="selectedLocations.includes(loc.value)"
                    @change="toggleLocation(loc.value)"
                    class="rounded border-outline text-primary focus:ring-primary h-4 w-4"
                  />
                  <span class="font-body-sm text-body-sm text-on-surface-variant group-hover:text-on-surface">
                    {{ loc.label }} ({{ loc.count }})
                  </span>
                </label>
              </div>
            </div>
          </div>
        </aside>

        <!-- Main Area: Company Grid -->
        <div class="flex-grow">
          <div class="flex justify-between items-center mb-md">
            <h2 class="font-headline-sm text-headline-sm text-on-background">
              All Companies 
              <span class="text-outline font-body-sm font-normal ml-2">
                Showing {{ companies.length }} of {{ meta?.total ?? 0 }}
              </span>
            </h2>
            <div class="flex items-center gap-2">
              <span class="font-body-sm text-body-sm text-on-surface-variant hidden sm:inline">Sort by:</span>
              <select 
                v-model="sortBy"
                class="bg-surface border border-outline-variant rounded-md text-body-sm font-body-sm text-on-surface py-1.5 pl-3 pr-8 focus:ring-1 focus:ring-primary outline-none"
              >
                <option value="Most Relevant">Most Relevant</option>
                <option value="Most Open Jobs">Most Open Jobs</option>
                <option value="Alphabetical">Alphabetical</option>
              </select>
            </div>
          </div>

          <!-- Loading state -->
          <div v-if="isPending" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-md mb-xl">
            <div 
              v-for="i in 6" 
              :key="i" 
              class="bg-surface-container-lowest rounded-xl p-md custom-shadow-soft border border-outline-variant flex flex-col opacity-60 animate-pulse"
            >
              <div class="w-12 h-12 bg-surface-variant rounded-lg mb-sm"></div>
              <div class="h-6 bg-surface-variant rounded w-3/4 mb-2"></div>
              <div class="h-4 bg-surface-variant rounded w-1/2 mb-4"></div>
              <div class="h-4 bg-surface-variant rounded w-2/3 mb-2"></div>
              <div class="h-4 bg-surface-variant rounded w-2/3 mb-md"></div>
              <div class="mt-auto pt-md border-t border-outline-variant/50 flex gap-2">
                <div class="h-8 bg-surface-variant rounded w-full"></div>
                <div class="h-8 bg-surface-variant rounded w-full"></div>
              </div>
            </div>
          </div>

          <!-- Error state -->
          <div v-else-if="isError" class="rounded-xl border border-outline-variant bg-surface-container-lowest p-8 text-center mb-xl">
            <p class="text-sm leading-relaxed text-on-surface-variant">Failed to load companies. Please try again.</p>
          </div>

          <!-- Empty state -->
          <div v-else-if="!companies.length" class="rounded-xl border border-outline-variant bg-surface-container-lowest p-12 text-center mb-xl">
            <Building2 class="mx-auto mb-3 h-12 w-12 text-outline-variant" />
            <h3 class="mb-1 text-lg font-semibold text-on-surface">No companies found</h3>
            <p class="text-sm leading-relaxed text-on-surface-variant">Try adjusting your filters or search query.</p>
            <Button variant="outline" class="mt-4" @click="clearAllFilters">Clear Filters</Button>
          </div>

          <!-- Grid -->
          <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-md mb-xl">
            <!-- Standard Card -->
            <div 
              v-for="company in companies"
              :key="company.id"
              class="bg-surface-container-lowest rounded-xl p-md custom-shadow-soft hover:custom-shadow-hover transition-all duration-200 border border-outline-variant flex flex-col"
            >
              <div class="flex justify-between items-start mb-sm">
                <!-- Logo -->
                <div class="w-12 h-12 rounded-lg bg-surface border border-outline-variant p-1.5 flex items-center justify-center">
                  <img v-if="company.logo_url" :src="company.logo_url" :alt="company.company_name" class="w-full h-full object-contain" />
                  <span v-else class="font-bold text-primary text-lg">{{ company.company_name?.charAt(0) || '?' }}</span>
                </div>
                <!-- Bookmark -->
                <button 
                  @click="toggleFollow(company.id)"
                  class="text-outline hover:text-primary transition-colors cursor-pointer"
                >
                  <Bookmark 
                    class="h-5 w-5" 
                    :class="{ 'fill-primary text-primary': followedCompanies.has(company.id) }" 
                  />
                </button>
              </div>
              
              <h3 class="font-headline-sm text-headline-sm text-on-background mb-1 truncate">
                {{ company.company_name }}
              </h3>
              
              <p class="font-body-sm text-body-sm text-on-surface-variant mb-3">
                {{ company.industry || 'Tech Company' }}
              </p>
              
              <div class="flex flex-col gap-2 mb-md">
                <div class="flex items-center gap-1 text-on-surface-variant font-body-sm text-body-sm">
                  <MapPin class="h-4 w-4 text-outline" /> 
                  {{ company.location || 'Remote' }}
                </div>
                <div class="flex items-center gap-1 text-on-surface-variant font-body-sm text-body-sm">
                  <Users class="h-4 w-4 text-outline" /> 
                  {{ company.employee_count ? company.employee_count + ' employees' : 'N/A employees' }}
                </div>
              </div>
              
              <div class="mt-auto pt-md border-t border-outline-variant/50">
                <div 
                  @click="router.push(`/jobs?q=${company.company_name}`)"
                  class="inline-flex items-center gap-1 bg-[#14B8A6]/10 text-[#14B8A6] px-2.5 py-1 rounded-full font-label-sm text-label-sm mb-md hover:bg-[#14B8A6]/20 transition-colors cursor-pointer"
                >
                  <Briefcase class="h-3.5 w-3.5 text-[#14B8A6]" /> 
                  {{ company.open_jobs_count }} open positions
                </div>
                <div class="flex gap-2">
                  <button 
                    @click="toggleFollow(company.id)"
                    class="flex-1 py-1.5 border rounded-md font-label-md text-label-md transition-colors cursor-pointer"
                    :class="followedCompanies.has(company.id) 
                      ? 'border-primary text-primary hover:bg-primary/5' 
                      : 'border-outline-variant text-on-surface hover:bg-surface-container'"
                  >
                    {{ followedCompanies.has(company.id) ? 'Following' : 'Follow' }}
                  </button>
                  <a 
                    @click.prevent="openWebsite(company.website)"
                    class="flex-grow py-1.5 bg-primary/10 text-primary rounded-md text-center font-label-md text-label-md hover:bg-primary-container hover:text-white transition-colors cursor-pointer" 
                    href="#"
                  >
                    Profile
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="meta && meta.last_page > 1" class="flex justify-center items-center gap-1 mt-lg">
            <button 
              :disabled="page <= 1"
              @click="page--"
              class="w-10 h-10 rounded-md border border-outline-variant flex items-center justify-center text-on-surface hover:bg-surface-container transition-colors disabled:opacity-50 cursor-pointer"
            >
              <ChevronLeft class="h-5 w-5" />
            </button>
            <button 
              v-for="p in meta.last_page"
              :key="p"
              @click="page = p"
              class="w-10 h-10 rounded-md font-label-md text-label-md flex items-center justify-center transition-colors cursor-pointer"
              :class="page === p
                ? 'bg-primary text-on-primary font-bold'
                : 'border border-outline-variant text-on-surface hover:bg-surface-container'"
            >
              {{ p }}
            </button>
            <button 
              :disabled="page >= meta.last_page"
              @click="page++"
              class="w-10 h-10 rounded-md border border-outline-variant flex items-center justify-center text-on-surface hover:bg-surface-container transition-colors cursor-pointer"
            >
              <ChevronRight class="h-5 w-5" />
            </button>
          </div>
        </div>
      </div>
    </main>

    <Footer />
  </div>
</template>

<style scoped>
.custom-shadow-soft { box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.05); }
.custom-shadow-hover { box-shadow: 0px 12px 24px rgba(0, 0, 0, 0.08); }
</style>
