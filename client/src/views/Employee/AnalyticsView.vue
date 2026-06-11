<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { Eye, Users, TrendingUp, AlertTriangle, BarChart3, Activity } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { toast } from "vue-sonner";
import { getEmployerAnalyticsApi, type AnalyticsData } from "@/api/employer";
import VueApexCharts from "vue3-apexcharts";

const loading = ref(true);
const analytics = ref<AnalyticsData | null>(null);
const error = ref("");

onMounted(async () => {
  try {
    const res = await getEmployerAnalyticsApi();
    analytics.value = res.data;
  } catch {
    error.value = "Failed to load analytics";
    toast.error(error.value);
  } finally {
    loading.value = false;
  }
});

const conversionPercent = computed(() => {
  if (!analytics.value) return "0";
  return (analytics.value.conversion_rate * 100).toFixed(1);
});

const barChartOptions = computed(() => ({
  chart: {
    type: "bar" as const,
    toolbar: { show: false },
    fontFamily: "Inter, sans-serif",
  },
  colors: ["#3b82f6"],
  plotOptions: {
    bar: { borderRadius: 4, columnWidth: "60%" },
  },
  dataLabels: { enabled: false },
  xaxis: {
    categories: analytics.value?.per_listing.map((l) => l.title) ?? [],
    labels: { style: { fontSize: "12px" } },
  },
  yaxis: {
    title: { text: "Applicants" },
  },
}));

const barChartSeries = computed(() => [
  {
    name: "Applicants",
    data: analytics.value?.per_listing.map((l) => l.applicants) ?? [],
  },
]);

const lineChartOptions = computed(() => ({
  chart: {
    type: "line" as const,
    toolbar: { show: false },
    fontFamily: "Inter, sans-serif",
  },
  colors: ["#10b981"],
  stroke: { curve: "smooth" as const, width: 2 },
  dataLabels: { enabled: false },
  xaxis: {
    categories: analytics.value?.views_over_time.map((v) => {
      const d = new Date(v.date);
      return d.toLocaleDateString("en-US", { month: "short", day: "numeric" });
    }) ?? [],
    labels: { style: { fontSize: "11px" } },
  },
  yaxis: {
    title: { text: "Views" },
  },
  fill: {
    type: "gradient",
    gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0 },
  },
}));

const lineChartSeries = computed(() => [
  {
    name: "Views",
    data: analytics.value?.views_over_time.map((v) => v.views) ?? [],
  },
]);
</script>

<template>
  <div class="min-h-screen bg-background text-on-background">
    <main class="mx-auto w-full max-w-container-max flex-1 p-md md:p-lg">
      <div class="mb-xl flex flex-col justify-between gap-sm md:flex-row md:items-center">
        <div>
          <h1 class="font-headline-lg text-headline-lg text-on-background">Analytics</h1>
          <p class="mt-1 font-body-md text-body-md text-on-surface-variant">
            Track your job listing performance.
          </p>
        </div>
      </div>

      <div v-if="loading" class="mb-xl grid grid-cols-1 gap-md md:grid-cols-3">
        <div v-for="i in 3" :key="i" class="h-28 animate-pulse rounded-xl bg-surface-container-high"></div>
      </div>

      <div v-else-if="error" class="mb-xl flex items-center gap-sm rounded-xl bg-surface-container-lowest p-md shadow-[0px_4px_12px_rgba(0,0,0,0.05)] font-body-sm text-body-sm text-destructive">
        <AlertTriangle class="h-5 w-5" />
        {{ error }}
      </div>

      <template v-else-if="analytics">
        <div class="mb-xl grid grid-cols-1 gap-md md:grid-cols-3">
          <article class="flex flex-col justify-between rounded-xl bg-surface-container-lowest p-md shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
            <div class="mb-sm flex items-start justify-between">
              <div class="flex h-10 w-10 items-center justify-center rounded-full bg-surface-container text-primary">
                <Eye class="h-5 w-5" />
              </div>
            </div>
            <div>
              <p class="mb-1 font-label-md text-label-md text-on-surface-variant">Total Views</p>
              <h3 class="font-headline-xl text-headline-xl text-on-background">{{ analytics.views }}</h3>
            </div>
          </article>

          <article class="flex flex-col justify-between rounded-xl bg-surface-container-lowest p-md shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
            <div class="mb-sm flex items-start justify-between">
              <div class="flex h-10 w-10 items-center justify-center rounded-full bg-surface-container text-secondary">
                <Users class="h-5 w-5" />
              </div>
            </div>
            <div>
              <p class="mb-1 font-label-md text-label-md text-on-surface-variant">Total Applicants</p>
              <h3 class="font-headline-xl text-headline-xl text-on-background">{{ analytics.applicants }}</h3>
            </div>
          </article>

          <article class="flex flex-col justify-between rounded-xl bg-surface-container-lowest p-md shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
            <div class="mb-sm flex items-start justify-between">
              <div class="flex h-10 w-10 items-center justify-center rounded-full bg-surface-container text-warning">
                <TrendingUp class="h-5 w-5" />
              </div>
            </div>
            <div>
              <p class="mb-1 font-label-md text-label-md text-on-surface-variant">Conversion Rate</p>
              <h3 class="font-headline-xl text-headline-xl text-on-background">{{ conversionPercent }}%</h3>
            </div>
          </article>
        </div>

        <div class="grid gap-lg lg:grid-cols-2">
          <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
            <div class="flex items-center gap-sm border-b border-outline-variant p-md">
              <BarChart3 class="h-5 w-5 text-primary" />
              <h2 class="font-headline-md text-headline-md text-on-background">Applicants per Listing</h2>
            </div>
            <div class="p-md">
              <VueApexCharts
                v-if="analytics.per_listing.length"
                type="bar"
                height="300"
                :options="barChartOptions"
                :series="barChartSeries"
              />
              <p v-else class="py-lg text-center font-body-sm text-body-sm text-on-surface-variant">No listing data available.</p>
            </div>
          </section>

          <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
            <div class="flex items-center gap-sm border-b border-outline-variant p-md">
              <Activity class="h-5 w-5 text-secondary" />
              <h2 class="font-headline-md text-headline-md text-on-background">Views Over Time</h2>
            </div>
            <div class="p-md">
              <VueApexCharts
                v-if="analytics.views_over_time.length"
                type="line"
                height="300"
                :options="lineChartOptions"
                :series="lineChartSeries"
              />
              <p v-else class="py-lg text-center font-body-sm text-body-sm text-on-surface-variant">No view data available.</p>
            </div>
          </section>
        </div>
      </template>

      <div v-else class="rounded-xl bg-surface-container-lowest p-lg text-center font-body-sm text-body-sm text-on-surface-variant shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
        No analytics data available yet.
      </div>
    </main>
  </div>
</template>
