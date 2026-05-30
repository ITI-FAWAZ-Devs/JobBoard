<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { getEmployerAnalyticsApi } from '@/api/employer';
import VueApexCharts from 'vue3-apexcharts';
import { Eye, FileText, UserCheck, Search, Filter, MoreHorizontal } from 'lucide-vue-next';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

const loading = ref(true);
const analyticsData = ref({
  total_profile_views: 0,
  total_applications: 0,
  interview_conversion: 0,
  jobs: [] as any[]
});

const chartOptions = computed(() => ({
  chart: {
    type: 'area' as const,
    toolbar: { show: false },
    fontFamily: 'Inter, sans-serif'
  },
  colors: ['#3b82f6', '#10b981'],
  dataLabels: { enabled: false },
  stroke: {
    curve: 'smooth' as const,
    width: 2
  },
  xaxis: {
    categories: analyticsData.value.jobs.length > 0 
      ? analyticsData.value.jobs.map(j => j.title.substring(0, 15) + (j.title.length > 15 ? '...' : '')) 
      : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
  },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.4,
      opacityTo: 0.05,
      stops: [0, 90, 100]
    }
  }
}));

const chartSeries = computed(() => {
  if (analyticsData.value.jobs.length === 0) return [];
  return [
    {
      name: 'Views',
      data: analyticsData.value.jobs.map(j => j.views_count || 0)
    },
    {
      name: 'Applications',
      data: analyticsData.value.jobs.map(j => j.applications_count || 0)
    }
  ];
});

onMounted(async () => {
  try {
    const res = await getEmployerAnalyticsApi();
    if (res.status === 'success') {
      analyticsData.value = res.data;
    }
  } catch (error) {
    console.error('Failed to load analytics', error);
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="p-8 max-w-7xl mx-auto space-y-8 animate-in fade-in zoom-in-95 duration-500">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold tracking-tight text-gray-900">Overview</h1>
        <p class="text-gray-500 mt-1">Welcome back. Here's what's happening with your job postings today.</p>
      </div>
      <Button class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-4 py-2 flex items-center gap-2 transition-all">
        <span class="text-lg">+</span> Post a Job
      </Button>
    </div>

    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-3 gap-6 animate-pulse">
      <div v-for="i in 3" :key="i" class="h-32 bg-gray-200 rounded-xl"></div>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Total Views -->
      <Card class="border-none shadow-sm ring-1 ring-gray-100 rounded-xl hover:shadow-md transition-all">
        <CardContent class="p-6">
          <div class="flex justify-between items-start">
            <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center">
              <Eye class="w-5 h-5" />
            </div>
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
              <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
              12%
            </span>
          </div>
          <div class="mt-4">
            <p class="text-sm font-medium text-gray-500">Total Profile Views</p>
            <h3 class="text-3xl font-bold text-gray-900 mt-1">{{ analyticsData.total_profile_views.toLocaleString() }}</h3>
          </div>
        </CardContent>
      </Card>

      <!-- Total Applications -->
      <Card class="border-none shadow-sm ring-1 ring-gray-100 rounded-xl hover:shadow-md transition-all">
        <CardContent class="p-6">
          <div class="flex justify-between items-start">
            <div class="w-10 h-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center">
              <FileText class="w-5 h-5" />
            </div>
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">
              <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
              8%
            </span>
          </div>
          <div class="mt-4">
            <p class="text-sm font-medium text-gray-500">Total Applications</p>
            <h3 class="text-3xl font-bold text-gray-900 mt-1">{{ analyticsData.total_applications.toLocaleString() }}</h3>
          </div>
        </CardContent>
      </Card>

      <!-- Conversion -->
      <Card class="border-none shadow-sm ring-1 ring-gray-100 rounded-xl hover:shadow-md transition-all">
        <CardContent class="p-6">
          <div class="flex justify-between items-start">
            <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
              <UserCheck class="w-5 h-5" />
            </div>
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
              <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
              0%
            </span>
          </div>
          <div class="mt-4">
            <p class="text-sm font-medium text-gray-500">Interview Conversion</p>
            <h3 class="text-3xl font-bold text-gray-900 mt-1">{{ analyticsData.interview_conversion }}%</h3>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Chart -->
    <Card class="border-none shadow-sm ring-1 ring-gray-100 rounded-xl overflow-hidden">
      <CardHeader class="border-b border-gray-50 bg-white/50 px-6 py-4">
        <CardTitle class="text-lg font-semibold text-gray-800">Engagement Over Time</CardTitle>
      </CardHeader>
      <CardContent class="p-6 bg-white">
         <VueApexCharts type="area" height="300" :options="chartOptions" :series="chartSeries" />
      </CardContent>
    </Card>

    <!-- Listings Table -->
    <Card class="border-none shadow-sm ring-1 ring-gray-100 rounded-xl overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between bg-white">
        <h2 class="text-xl font-bold text-gray-900">My Job Listings</h2>
        <div class="flex items-center gap-3">
          <div class="relative">
            <Search class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input type="text" placeholder="Search listings..." class="pl-9 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all w-64" />
          </div>
          <Button variant="outline" size="icon" class="border-gray-200 text-gray-600 hover:bg-gray-50">
            <Filter class="w-4 h-4" />
          </Button>
        </div>
      </div>
      
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600">
          <thead class="bg-gray-50/50 text-xs uppercase text-gray-500 font-semibold tracking-wider">
            <tr>
              <th class="px-6 py-4">Job Title</th>
              <th class="px-6 py-4">Status</th>
              <th class="px-6 py-4">Posted Date</th>
              <th class="px-6 py-4">Views</th>
              <th class="px-6 py-4">Applications</th>
              <th class="px-6 py-4">Conversion Rate</th>
              <th class="px-6 py-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 bg-white">
            <tr v-if="loading">
              <td colspan="5" class="px-6 py-8 text-center text-gray-400">Loading listings...</td>
            </tr>
            <tr v-else-if="analyticsData.jobs.length === 0">
              <td colspan="5" class="px-6 py-8 text-center text-gray-400">No job listings found.</td>
            </tr>
            <tr v-for="job in analyticsData.jobs" :key="job.id" class="hover:bg-gray-50/50 transition-colors group">
              <td class="px-6 py-4">
                <div class="font-medium text-gray-900 group-hover:text-blue-600 transition-colors">{{ job.title }}</div>
                <div class="text-gray-500 mt-0.5 text-xs">{{ job.location || 'Remote' }} • {{ job.work_type }}</div>
              </td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                  :class="{
                    'bg-blue-50 text-blue-700': job.status === 'approved',
                    'bg-amber-50 text-amber-700': job.status === 'pending',
                    'bg-red-50 text-red-700': job.status === 'rejected',
                  }">
                  <span class="w-1.5 h-1.5 rounded-full" 
                    :class="{
                      'bg-blue-500': job.status === 'approved',
                      'bg-amber-500': job.status === 'pending',
                      'bg-red-500': job.status === 'rejected',
                    }"></span>
                  {{ job.status === 'approved' ? 'Active' : job.status.charAt(0).toUpperCase() + job.status.slice(1) }}
                </span>
              </td>
              <td class="px-6 py-4">
                {{ new Date(job.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }}
              </td>
              <td class="px-6 py-4 font-semibold text-gray-900">
                {{ job.views_count || '-' }}
              </td>
              <td class="px-6 py-4 font-semibold text-gray-900">
                {{ job.applications_count || '-' }}
              </td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                      :class="job.conversion_rate >= 5 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'">
                  {{ job.conversion_rate || 0 }}%
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <Button variant="ghost" size="icon" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full w-8 h-8 p-0">
                  <MoreHorizontal class="w-4 h-4" />
                </Button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-white text-sm text-gray-500">
        <span v-if="!loading">Showing {{ analyticsData.jobs.length }} listings</span>
        <div class="flex gap-2">
          <Button variant="outline" size="sm" class="border-gray-200">Prev</Button>
          <Button variant="outline" size="sm" class="border-gray-200">Next</Button>
        </div>
      </div>
    </Card>
  </div>
</template>
