<script setup lang="ts">
import { computed, ref } from "vue";
import { 
  Search, 
  MapPin, 
  BarChart3, 
  ChevronDown, 
  Plus, 
  X, 
  Building2,
  TrendingUp
} from "lucide-vue-next";
import Navbar from "@/components/shared/Navbar.vue";
import Footer from "@/components/shared/Footer.vue";
import { Button } from "@/components/ui/button";
import { toast } from "vue-sonner";
import { useRouter } from "vue-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/vue-query";
import { getSalaryReportsApi, postSalaryReportApi, getTopPayingCompaniesApi, type SalaryReportItem } from "@/api/jobs";

const router = useRouter();
const queryClient = useQueryClient();

// Search and filter states
const searchVal = ref("");
const searchQuery = ref("");
const selectedLocation = ref("All Locations");
const selectedCategories = ref<string[]>([]);
const selectedLevels = ref<string[]>([]);
const visibleCount = ref(3);

// Popular company salaries (fetched from backend)
const { data: topPayingResponse } = useQuery({
  queryKey: ["top-paying-companies"],
  queryFn: getTopPayingCompaniesApi,
});

const topCompanies = computed(() => topPayingResponse.value?.data ?? []);


// Modal state
const isModalOpen = ref(false);
const selectedBreakdown = ref<SalaryReportItem | null>(null);
const newTitle = ref("");
const newLocation = ref("Remote");
const newLevel = ref("Mid Level");
const newCategory = ref("Engineering");
const newSalary = ref<number | null>(null);

// Methods
function handlePopularSearch(roleName: string) {
  searchVal.value = roleName;
  searchQuery.value = roleName;
  visibleCount.value = 3;
}

function triggerSearch() {
  searchQuery.value = searchVal.value;
  visibleCount.value = 3;
}

function toggleCategory(cat: string) {
  const idx = selectedCategories.value.indexOf(cat);
  if (idx > -1) {
    selectedCategories.value.splice(idx, 1);
  } else {
    selectedCategories.value.push(cat);
  }
  visibleCount.value = 3;
}

function toggleLevel(level: string) {
  const idx = selectedLevels.value.indexOf(level);
  if (idx > -1) {
    selectedLevels.value.splice(idx, 1);
  } else {
    selectedLevels.value.push(level);
  }
  visibleCount.value = 3;
}

function formatAmount(val: number): string {
  return val.toLocaleString();
}

// Progress bar range style calculations
function formatShortAmount(val: number): string {
  return val >= 1000 ? `${(val / 1000).toFixed(0)}k` : val.toString();
}

function getRangeProgressStyle(min: number, max: number) {
  const maxLimit = max * 1.2;
  const left = (min / maxLimit) * 100;
  const right = 100 - (max / maxLimit) * 100;
  return {
    left: `${left}%`,
    right: `${right}%`
  };
}

function getMedianMarkerStyle(median: number, max: number) {
  const maxLimit = max * 1.2;
  const left = (median / maxLimit) * 100;
  return {
    left: `calc(${left}% - 2px)`
  };
}

// Backend API Filters binding
const salaryFilters = computed(() => {
  return {
    q: searchQuery.value || undefined,
    location: selectedLocation.value !== "All Locations" ? selectedLocation.value : undefined,
    category: selectedCategories.value.length ? selectedCategories.value : undefined,
    level: selectedLevels.value.length ? selectedLevels.value : undefined,
  };
});

// Vue Query implementation
const { data: apiResponse, isPending, isError } = useQuery({
  queryKey: ["salaries", salaryFilters],
  queryFn: () => getSalaryReportsApi(salaryFilters.value),
});

const salariesData = computed<SalaryReportItem[]>(() => apiResponse.value?.data ?? []);

const filteredSalaries = computed(() => {
  return salariesData.value;
});

const displayedSalaries = computed(() => {
  return filteredSalaries.value.slice(0, visibleCount.value);
});

// Contribute salary mutation
const submitMutation = useMutation({
  mutationFn: postSalaryReportApi,
  onSuccess: () => {
    toast.success("Thank you! Your salary report was submitted anonymously.");
    queryClient.invalidateQueries({ queryKey: ["salaries"] });
    // Reset fields & Close modal
    newTitle.value = "";
    newSalary.value = null;
    isModalOpen.value = false;
  },
  onError: () => {
    toast.error("Failed to submit salary report. Please try again.");
  }
});

function submitSalary() {
  if (!newTitle.value.trim() || !newSalary.value || newSalary.value <= 0) {
    toast.error("Please fill in all required fields with valid values.");
    return;
  }

  submitMutation.mutate({
    title: newTitle.value,
    location: newLocation.value,
    level: newLevel.value,
    category: newCategory.value,
    salary: newSalary.value,
  });
}

function goToCompany(companyName: string) {
  router.push(`/companies?q=${encodeURIComponent(companyName)}`);
}
</script>

<template>
  <div class="bg-surface text-on-surface font-body-md antialiased min-h-screen flex flex-col">
    <!-- Navbar -->
    <Navbar />

    <!-- Hero Section -->
    <section class="w-full pt-xl pb-lg px-md text-center bg-gradient-to-b from-surface-container-low to-surface">
      <div class="max-w-3xl mx-auto">
        <h1 class="font-headline-xl text-headline-xl text-on-surface mb-4">Salary Guide</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant mb-8 leading-relaxed">
          Discover your market value and explore salary trends across industries.
        </p>

        <!-- Search Bar -->
        <div class="relative max-w-2xl mx-auto shadow-[0px_4px_12px_rgba(0,0,0,0.05)] rounded-full bg-surface-container-lowest border border-outline-variant p-1.5 focus-within:border-primary focus-within:ring-1 focus-within:ring-primary flex items-center transition-all">
          <Search class="text-on-surface-variant ml-4 h-5 w-5 pointer-events-none flex-shrink-0" />
          <input 
            v-model="searchVal"
            @keyup.enter="triggerSearch"
            class="w-full pl-3 pr-4 py-2 bg-transparent border-0 outline-none focus:ring-0 focus:ring-offset-0 focus:outline-none font-body-md text-body-md text-on-surface placeholder:text-outline" 
            placeholder="Search salary by job title or role" 
            type="text"
          />
          <button 
            @click="triggerSearch"
            class="bg-primary text-on-primary px-6 py-2 rounded-full font-label-md text-label-md hover:bg-primary-container transition-colors flex-shrink-0 cursor-pointer shadow-sm"
          >
            Search
          </button>
        </div>

        <!-- Popular Searches -->
        <div class="mt-6 flex flex-wrap justify-center gap-xs">
          <button 
            @click="handlePopularSearch('Software Engineer')"
            class="bg-surface-container text-on-surface-variant px-3 py-1 rounded-full font-label-sm text-label-sm hover:bg-surface-variant transition-colors cursor-pointer border-none"
          >
            Software Engineer
          </button>
          <button 
            @click="handlePopularSearch('Product Manager')"
            class="bg-surface-container text-on-surface-variant px-3 py-1 rounded-full font-label-sm text-label-sm hover:bg-surface-variant transition-colors cursor-pointer border-none"
          >
            Product Manager
          </button>
          <button 
            @click="handlePopularSearch('Data Analyst')"
            class="bg-surface-container text-on-surface-variant px-3 py-1 rounded-full font-label-sm text-label-sm hover:bg-surface-variant transition-colors cursor-pointer border-none"
          >
            Data Analyst
          </button>
          <button 
            @click="handlePopularSearch('UI/UX Designer')"
            class="bg-surface-container text-on-surface-variant px-3 py-1 rounded-full font-label-sm text-label-sm hover:bg-surface-variant transition-colors cursor-pointer border-none"
          >
            UI/UX Designer
          </button>
          <button 
            @click="handlePopularSearch('Marketing Manager')"
            class="bg-surface-container text-on-surface-variant px-3 py-1 rounded-full font-label-sm text-label-sm hover:bg-surface-variant transition-colors cursor-pointer border-none"
          >
            Marketing Manager
          </button>
        </div>
      </div>
    </section>

    <!-- Main Layout -->
    <main class="max-w-container-max w-full mx-auto px-md py-lg grid grid-cols-1 md:grid-cols-12 gap-md items-start flex-grow">
      
      <!-- Left Sidebar Filters -->
      <aside class="md:col-span-3 bg-surface-container-lowest p-md rounded-xl shadow-[0px_4px_12px_rgba(0,0,0,0.05)] border border-surface-variant hidden md:block sticky top-[96px]">
        <h2 class="font-headline-sm text-headline-sm mb-6 border-b border-outline-variant pb-2 leading-snug">Filters</h2>
        
        <!-- Filter Group: Location -->
        <div class="mb-6">
          <h3 class="font-label-md text-label-md text-on-surface-variant mb-3">Location</h3>
          <div class="relative">
            <select 
              v-model="selectedLocation"
              @change="visibleCount = 3"
              class="w-full pl-3 pr-10 py-2 rounded-lg border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary font-body-sm text-body-sm text-on-surface bg-surface appearance-none outline-none cursor-pointer"
            >
              <option value="All Locations">All Locations</option>
              <option value="Egypt">Egypt</option>
              <option value="Remote">Remote</option>
              <option value="USA">USA</option>
              <option value="UK">UK</option>
            </select>
            <ChevronDown class="absolute right-3 top-1/2 -translate-y-1/2 text-outline h-4 w-4 pointer-events-none" />
          </div>
        </div>

        <!-- Filter Group: Job Category -->
        <div class="mb-6">
          <h3 class="font-label-md text-label-md text-on-surface-variant mb-3">Job Category</h3>
          <div class="space-y-2">
            <label 
              v-for="cat in ['Engineering', 'Design', 'Product', 'Marketing']"
              :key="cat"
              class="flex items-center gap-2 cursor-pointer group"
            >
              <input 
                type="checkbox"
                :checked="selectedCategories.includes(cat)"
                @change="toggleCategory(cat)"
                class="rounded border-outline-variant text-primary focus:ring-primary h-4 w-4"
              />
              <span class="font-body-sm text-body-sm text-on-surface-variant group-hover:text-primary transition-colors leading-relaxed">
                {{ cat }}
              </span>
            </label>
          </div>
        </div>

        <!-- Filter Group: Experience Level -->
        <div>
          <h3 class="font-label-md text-label-md text-on-surface-variant mb-3">Experience Level</h3>
          <div class="space-y-2">
            <label 
              v-for="lvl in ['Entry Level', 'Mid Level', 'Senior Level', 'Lead / Manager']"
              :key="lvl"
              class="flex items-center gap-2 cursor-pointer group"
            >
              <input 
                type="checkbox"
                :checked="selectedLevels.includes(lvl)"
                @change="toggleLevel(lvl)"
                class="rounded border-outline-variant text-primary focus:ring-primary h-4 w-4"
              />
              <span class="font-body-sm text-body-sm text-on-surface-variant group-hover:text-primary transition-colors leading-relaxed">
                {{ lvl }}
              </span>
            </label>
          </div>
        </div>
      </aside>

      <!-- Center Main Grid -->
      <div class="md:col-span-6 space-y-md">
        
        <!-- Loading State -->
        <div v-if="isPending" class="space-y-md">
          <div 
            v-for="i in 3" 
            :key="i" 
            class="bg-surface-container-lowest p-md rounded-xl shadow-[0px_4px_12px_rgba(0,0,0,0.05)] border border-outline-variant flex flex-col opacity-60 animate-pulse h-[200px]"
          >
            <div class="w-12 h-12 bg-surface-variant rounded-lg mb-sm"></div>
            <div class="h-6 bg-surface-variant rounded w-3/4 mb-2"></div>
            <div class="h-4 bg-surface-variant rounded w-1/2 mb-4"></div>
            <div class="h-4 bg-surface-variant rounded w-2/3 mb-md"></div>
          </div>
        </div>

        <!-- Error state -->
        <div v-else-if="isError" class="bg-surface-container-lowest p-xl rounded-xl shadow-[0px_4px_12px_rgba(0,0,0,0.05)] border border-surface-variant text-center">
          <TrendingUp class="mx-auto mb-3 h-12 w-12 text-outline-variant animate-bounce" />
          <h3 class="mb-1 text-lg font-semibold text-on-surface">Failed to load salaries</h3>
          <p class="text-sm text-on-surface-variant leading-relaxed">
            Please check your server connection and try again.
          </p>
        </div>

        <!-- Empty State -->
        <div v-else-if="!isPending && !filteredSalaries.length" class="bg-surface-container-lowest p-xl rounded-xl shadow-[0px_4px_12px_rgba(0,0,0,0.05)] border border-surface-variant text-center">
          <TrendingUp class="mx-auto mb-3 h-12 w-12 text-outline-variant" />
          <h3 class="mb-1 text-lg font-semibold text-on-surface">No salary data found</h3>
          <p class="text-sm text-on-surface-variant leading-relaxed mb-4">
            Try adjusting your search query or filter checkboxes to see more salary listings.
          </p>
          <Button variant="outline" @click="searchVal = ''; searchQuery = ''; selectedLocation = 'All Locations'; selectedCategories = []; selectedLevels = []; visibleCount = 3">
            Reset All Filters
          </Button>
        </div>

        <!-- Salary Card -->
        <div 
          v-else
          v-for="item in displayedSalaries"
          :key="item.id"
          class="bg-surface-container-lowest p-md rounded-xl shadow-[0px_4px_12px_rgba(0,0,0,0.05)] border border-surface-variant hover:shadow-[0px_8px_24px_rgba(0,0,0,0.08)] transition-shadow duration-200"
        >
          <div class="flex justify-between items-start mb-4">
            <div class="min-w-0 flex-1">
              <h3 class="font-headline-sm text-headline-sm text-on-surface truncate leading-snug">
                {{ item.title }}
              </h3>
              <p class="font-body-sm text-body-sm text-on-surface-variant mt-1 leading-relaxed truncate">
                {{ item.location }}
              </p>
            </div>
            <span class="bg-surface-container px-3 py-1 rounded-full font-label-sm text-label-sm text-on-surface-variant flex-shrink-0">
              {{ item.level }}
            </span>
          </div>

          <div class="mb-6">
            <div class="font-headline-lg text-headline-lg text-[#14B8A6] leading-snug">
              {{ formatAmount(item.medianSalary) }} {{ item.currency }}
              <span class="font-body-sm text-body-sm text-on-surface-variant font-normal">/ mo</span>
            </div>
            <p class="font-body-sm text-body-sm text-on-surface-variant flex items-center gap-1 mt-1 leading-relaxed">
              <BarChart3 class="h-4 w-4 text-outline" />
              Based on {{ item.reportCount }} reports
            </p>
          </div>

          <!-- Range Slider Indicator -->
          <div class="mb-6">
            <div class="flex justify-between font-label-sm text-label-sm text-on-surface-variant mb-2">
              <span>Min: {{ formatShortAmount(item.minSalary) }}</span>
              <span class="font-semibold text-primary">Median</span>
              <span>Max: {{ formatShortAmount(item.maxSalary) }}</span>
            </div>
            <div class="h-2 bg-surface-variant rounded-full relative w-full overflow-visible">
              <!-- Background Bar segment -->
              <div 
                class="absolute top-0 bottom-0 bg-[#14B8A6]/20 rounded-full"
                :style="getRangeProgressStyle(item.minSalary, item.maxSalary)"
              ></div>
              <!-- Median Marker -->
              <div 
                class="absolute top-[-2px] h-3 w-1 bg-[#14B8A6] rounded-full z-10"
                :style="getMedianMarkerStyle(item.medianSalary, item.maxSalary)"
              ></div>
            </div>
          </div>

          <button 
            @click="selectedBreakdown = item"
            class="w-full py-2 rounded-lg border border-outline-variant font-label-md text-label-md text-on-surface hover:bg-surface-container transition-colors cursor-pointer bg-transparent"
          >
            View Detailed Breakdown
          </button>
        </div>

        <button 
          v-if="visibleCount < filteredSalaries.length"
          @click="visibleCount += 3"
          class="w-full py-3 text-primary font-label-md text-label-md hover:underline decoration-2 underline-offset-4 flex justify-center items-center gap-2 cursor-pointer bg-transparent border-none"
        >
          Show more salaries
          <ChevronDown class="h-4 w-4" />
        </button>
      </div>

      <!-- Right Sidebar: Top Paying Companies -->
      <aside class="md:col-span-3 space-y-md">
        <div class="bg-surface-container-lowest p-md rounded-xl shadow-[0px_4px_12px_rgba(0,0,0,0.05)] border border-surface-variant">
          <h2 class="font-headline-sm text-headline-sm mb-4 leading-snug">Top Paying Companies</h2>
          <p class="font-body-sm text-body-sm text-on-surface-variant mb-6 leading-relaxed">
            For Senior Frontend Engineers in Remote
          </p>
          <div class="space-y-4">
            <!-- Company -->
            <div 
              v-for="co in topCompanies"
              :key="co.name"
              @click="goToCompany(co.name)"
              class="flex items-center justify-between group cursor-pointer"
            >
              <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 bg-surface border border-outline-variant rounded-lg flex items-center justify-center font-bold text-primary flex-shrink-0">
                  {{ co.initial }}
                </div>
                <div class="min-w-0">
                  <h4 class="font-label-md text-label-md group-hover:text-primary transition-colors truncate">
                    {{ co.name }}
                  </h4>
                  <p class="font-label-sm text-label-sm text-on-surface-variant leading-none">
                    {{ co.count }} salaries
                  </p>
                </div>
              </div>
              <span class="font-label-md text-label-md text-on-surface flex-shrink-0">
                {{ co.maxSalary }}
              </span>
            </div>
          </div>
          <button 
            @click="router.push('/companies')"
            class="w-full mt-6 py-2 rounded-lg border border-outline-variant font-label-md text-label-md text-on-surface hover:bg-surface-container transition-colors cursor-pointer bg-transparent"
          >
            View All Companies
          </button>
        </div>

        <!-- Promo Card -->
        <div class="bg-primary text-on-primary p-md rounded-xl shadow-[0px_4px_12px_rgba(0,0,0,0.05)] relative overflow-hidden">
          <div class="absolute -right-10 -top-10 w-32 h-32 bg-white opacity-10 rounded-full"></div>
          <h3 class="font-headline-sm text-headline-sm mb-2 relative z-10">Contribute your salary</h3>
          <p class="font-body-sm text-body-sm mb-4 relative z-10 opacity-90 leading-relaxed">
            Help the community by sharing your salary anonymously.
          </p>
          <button 
            @click="isModalOpen = true"
            class="bg-surface-container-lowest text-primary px-4 py-2 rounded-lg font-label-md text-label-md hover:bg-surface transition-colors w-full relative z-10 cursor-pointer border-none shadow-sm"
          >
            Add a Salary
          </button>
        </div>
      </aside>

    </main>

    <!-- Detailed Breakdown Modal Overlay -->
    <div 
      v-if="selectedBreakdown"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 animate-in fade-in duration-300"
    >
      <div 
        class="bg-surface-container-lowest rounded-2xl shadow-xl border border-outline-variant/30 w-full max-w-2xl p-6 md:p-8 relative animate-in zoom-in duration-300"
      >
        <button 
          @click="selectedBreakdown = null"
          class="absolute right-4 top-4 p-1 rounded-full text-outline-variant hover:text-on-surface transition-colors cursor-pointer border-none bg-transparent"
        >
          <X class="h-5 w-5" />
        </button>

        <h3 class="font-headline-sm text-headline-sm text-on-surface mb-1 pr-6 truncate">
          {{ selectedBreakdown.title }}
        </h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant mb-6 leading-relaxed">
          Detailed compensation breakdown for {{ selectedBreakdown.level }} in {{ selectedBreakdown.location }}.
        </p>

        <!-- Breakdown Charts -->
        <div class="space-y-4">
          <!-- Item 1: Base Salary -->
          <div>
            <div class="flex justify-between font-label-md text-label-md text-on-surface mb-1">
              <span>Base Pay (80%)</span>
              <span>{{ formatAmount(Math.round(selectedBreakdown.medianSalary * 0.8)) }} {{ selectedBreakdown.currency }}</span>
            </div>
            <div class="h-2 bg-surface-variant rounded-full w-full overflow-hidden">
              <div class="h-full bg-primary rounded-full" style="width: 80%"></div>
            </div>
          </div>

          <!-- Item 2: Cash Bonus -->
          <div>
            <div class="flex justify-between font-label-md text-label-md text-on-surface mb-1">
              <span>Cash Bonus & Perks (12%)</span>
              <span>{{ formatAmount(Math.round(selectedBreakdown.medianSalary * 0.12)) }} {{ selectedBreakdown.currency }}</span>
            </div>
            <div class="h-2 bg-surface-variant rounded-full w-full overflow-hidden">
              <div class="h-full bg-[#14B8A6] rounded-full" style="width: 12%"></div>
            </div>
          </div>

          <!-- Item 3: Equity -->
          <div>
            <div class="flex justify-between font-label-md text-label-md text-on-surface mb-1">
              <span>Stock & Equity (8%)</span>
              <span>{{ formatAmount(Math.round(selectedBreakdown.medianSalary * 0.08)) }} {{ selectedBreakdown.currency }}</span>
            </div>
            <div class="h-2 bg-surface-variant rounded-full w-full overflow-hidden">
              <div class="h-full bg-secondary-fixed-dim rounded-full" style="width: 8%"></div>
            </div>
          </div>
        </div>

        <div class="mt-6 p-sm rounded-lg bg-surface border border-outline-variant/60 flex items-start gap-2">
          <BarChart3 class="h-5 w-5 text-primary flex-shrink-0 mt-0.5" />
          <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed">
            Estimates are derived anonymized from {{ selectedBreakdown.reportCount }} self-reported entries within the community. Actual compensation may vary based on exact candidates' experience, benefits, and company scale.
          </p>
        </div>

        <button 
          @click="selectedBreakdown = null"
          class="w-full mt-6 py-2 rounded-lg bg-primary text-on-primary font-label-md text-label-md hover:bg-primary-container transition-colors cursor-pointer border-none shadow-sm"
        >
          Close Breakdown
        </button>
      </div>
    </div>

    <!-- Contribution Modal Overlay -->
    <div 
      v-if="isModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 animate-in fade-in duration-300"
    >
      <div 
        class="bg-surface-container-lowest rounded-2xl shadow-xl border border-outline-variant/30 w-full max-w-3xl p-6 md:p-8 relative animate-in zoom-in duration-300"
      >
        <button 
          @click="isModalOpen = false"
          class="absolute right-4 top-4 p-1 rounded-full text-outline-variant hover:text-on-surface transition-colors cursor-pointer border-none bg-transparent"
        >
          <X class="h-5 w-5" />
        </button>

        <h3 class="font-headline-sm text-headline-sm text-on-surface mb-2 pr-6">
          Submit Anonymous Salary
        </h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant mb-6 leading-relaxed">
          Provide anonymous insights to help other candidates negotiate better compensation.
        </p>

        <!-- Form fields -->
        <div class="space-y-4">
          <div>
            <label class="block font-label-md text-label-md text-on-surface mb-1.5">Job Title *</label>
            <input 
              v-model="newTitle"
              type="text"
              placeholder="e.g. Senior Frontend Engineer"
              class="w-full px-sm py-2 bg-surface border border-outline-variant rounded-md focus:border-primary focus:ring-1 focus:ring-primary outline-none font-body-sm text-body-sm text-on-surface"
            />
          </div>

          <div class="grid grid-cols-2 gap-sm">
            <div>
              <label class="block font-label-md text-label-md text-on-surface mb-1.5">Location</label>
              <input 
                v-model="newLocation"
                type="text"
                placeholder="e.g. Cairo, Egypt or Remote"
                class="w-full px-sm py-2 bg-surface border border-outline-variant rounded-md focus:border-primary focus:ring-1 focus:ring-primary outline-none font-body-sm text-body-sm text-on-surface"
              />
            </div>
            <div>
              <label class="block font-label-md text-label-md text-on-surface mb-1.5">Category</label>
              <select 
                v-model="newCategory"
                class="w-full px-sm py-2 bg-surface border border-outline-variant rounded-md focus:border-primary focus:ring-1 focus:ring-primary outline-none font-body-sm text-body-sm text-on-surface cursor-pointer"
              >
                <option value="Engineering">Engineering</option>
                <option value="Design">Design</option>
                <option value="Product">Product</option>
                <option value="Marketing">Marketing</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-sm">
            <div>
              <label class="block font-label-md text-label-md text-on-surface mb-1.5">Experience Level</label>
              <select 
                v-model="newLevel"
                class="w-full px-sm py-2 bg-surface border border-outline-variant rounded-md focus:border-primary focus:ring-1 focus:ring-primary outline-none font-body-sm text-body-sm text-on-surface cursor-pointer"
              >
                <option value="Entry Level">Entry Level</option>
                <option value="Mid Level">Mid Level</option>
                <option value="Senior Level">Senior Level</option>
                <option value="Lead / Manager">Lead / Manager</option>
              </select>
            </div>
            <div>
              <label class="block font-label-md text-label-md text-on-surface mb-1.5">Monthly Salary (EGP) *</label>
              <input 
                v-model.number="newSalary"
                type="number"
                placeholder="e.g. 50000"
                class="w-full px-sm py-2 bg-surface border border-outline-variant rounded-md focus:border-primary focus:ring-1 focus:ring-primary outline-none font-body-sm text-body-sm text-on-surface"
              />
            </div>
          </div>
        </div>

        <div class="mt-8 flex gap-sm">
          <button 
            @click="isModalOpen = false"
            class="flex-1 py-2 rounded-lg border border-outline-variant font-label-md text-label-md text-on-surface hover:bg-surface-container transition-colors cursor-pointer bg-transparent"
          >
            Cancel
          </button>
          <button 
            @click="submitSalary"
            class="flex-1 py-2 rounded-lg bg-primary text-on-primary font-label-md text-label-md hover:bg-primary-container transition-colors cursor-pointer border-none shadow-sm"
          >
            Submit Report
          </button>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <Footer />
  </div>
</template>

<style scoped>
/* Scoped custom shadow parameters matching Material Design specs */
.shadow-soft {
  box-shadow: 0 1px 2px rgb(0 0 0 / 0.05), 0 4px 12px rgb(0 0 0 / 0.04);
}
</style>
