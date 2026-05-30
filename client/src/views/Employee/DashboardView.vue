<script setup lang="ts">
import {
  BarChart3,
  Bell,
  BriefcaseBusiness,
  Eye,
  FileText,
  Filter,
  Mail,
  Menu,
  MoreHorizontal,
  Plus,
  Search,
  // TrendingRight,
  TrendingUp,
  UserCheck,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { RouterLink } from 'vue-router';

const stats = [
  {
    label: 'Total Profile Views',
    value: '24,592',
    icon: Eye,
    trend: '12%',
    trendIcon: TrendingUp,
    muted: false,
  },
  {
    label: 'Total Applications',
    value: '1,204',
    icon: FileText,
    trend: '8%',
    trendIcon: TrendingUp,
    muted: false,
  },
  {
    label: 'Interview Conversion',
    value: '18.5%',
    icon: UserCheck,
    trend: '0%',
    // trendIcon: TrendingRight,
    muted: true,
  },
];

const jobs = [
  {
    title: 'Senior Frontend Engineer',
    meta: 'San Francisco, CA • Full-time',
    status: 'Active',
    statusClass: 'bg-surface-container text-primary',
    dotClass: 'bg-primary',
    date: 'Oct 24, 2023',
    applications: '142',
  },
  {
    title: 'Product Designer',
    meta: 'Remote • Contract',
    status: 'Pending',
    statusClass: 'bg-surface-variant text-on-surface-variant',
    dotClass: 'bg-on-surface-variant',
    date: 'Oct 28, 2023',
    applications: '-',
  },
  {
    title: 'Marketing Manager',
    meta: 'New York, NY • Full-time',
    status: 'Rejected',
    statusClass: 'bg-error-container text-on-error-container',
    dotClass: 'bg-error',
    date: 'Oct 15, 2023',
    applications: '0',
  },
];

</script>

<template>
  <div class="min-h-screen bg-background text-on-background">
    <main class="flex min-h-screen flex-1 flex-col">
      <header class="sticky top-0 z-10 flex h-18 items-center justify-between border-b border-outline-variant bg-surface px-md md:hidden">
        <div class="flex items-center gap-sm">
          <Button variant="ghost" size="icon" class="cursor-pointer text-on-surface-variant">
            <Menu class="h-5 w-5" aria-hidden="true" />
          </Button>
          <span class="font-headline-sm text-headline-sm font-bold text-primary">WorkHive</span>
        </div>
        <Button variant="ghost" size="icon" class="cursor-pointer text-on-surface-variant">
          <Bell class="h-5 w-5" aria-hidden="true" />
        </Button>
      </header>

      <div class="mx-auto w-full max-w-container-max flex-1 p-md md:p-lg">
        <div class="mb-xl flex flex-col justify-between gap-sm md:flex-row md:items-center">
          <div>
            <h1 class="font-headline-lg text-headline-lg text-on-background">Overview</h1>
            <p class="mt-1 font-body-md text-body-md text-on-surface-variant">
              Welcome back. Here's what's happening with your job postings today.
            </p>
          </div>
          <Button class="flex w-full cursor-pointer items-center justify-center gap-xs rounded-lg bg-primary-container px-md py-sm font-label-md text-label-md text-on-primary transition-opacity hover:opacity-90 md:w-auto">
            <Plus class="h-5 w-5" aria-hidden="true" />
            Post a Job
          </Button>
        </div>

        <section class="mb-xl grid grid-cols-1 gap-md md:grid-cols-3">
          <article
            v-for="stat in stats"
            :key="stat.label"
            class="flex flex-col justify-between rounded-xl bg-surface-container-lowest p-md shadow-[0px_4px_12px_rgba(0,0,0,0.05)]"
          >
            <div class="mb-sm flex items-start justify-between">
              <div class="flex h-10 w-10 items-center justify-center rounded-full bg-surface-container text-primary">
                <component :is="stat.icon" class="h-5 w-5" aria-hidden="true" />
              </div>
              <span
                class="flex items-center gap-1 rounded-full bg-surface-container-high px-2 py-1 font-label-sm text-label-sm"
                :class="stat.muted ? 'text-on-surface-variant' : 'text-on-surface'"
              >
                <component :is="stat.trendIcon" class="h-3.5 w-3.5" aria-hidden="true" />
                {{ stat.trend }}
              </span>
            </div>
            <div>
              <p class="mb-1 font-label-md text-label-md text-on-surface-variant">{{ stat.label }}</p>
              <h3 class="font-headline-xl text-headline-xl text-on-background">{{ stat.value }}</h3>
            </div>
          </article>
        </section>

        <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
          <div class="flex flex-col items-start justify-between gap-sm border-b border-outline-variant p-md sm:flex-row sm:items-center">
            <h2 class="font-headline-md text-headline-md text-on-background">My Job Listings</h2>
            <div class="flex w-full gap-sm sm:w-auto">
              <div class="relative w-full sm:w-64">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-on-surface-variant" aria-hidden="true" />
                <input
                  class="w-full rounded-lg border border-outline-variant bg-surface-container-lowest py-2 pl-10 pr-4 font-body-sm text-body-sm placeholder:text-on-surface-variant focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                  placeholder="Search listings..."
                  type="text"
                />
              </div>
              <Button variant="outline" size="icon" class="cursor-pointer rounded-lg border-outline-variant text-on-surface-variant hover:bg-surface-variant">
                <Filter class="h-4 w-4" aria-hidden="true" />
              </Button>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left">
              <thead>
                <tr class="border-b border-outline-variant bg-surface">
                  <th class="p-md font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Job Title</th>
                  <th class="p-md font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Status</th>
                  <th class="p-md font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Posted Date</th>
                  <th class="p-md text-right font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Applications</th>
                  <th class="p-md text-center font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-outline-variant">
                <tr v-for="job in jobs" :key="job.title" class="group transition-colors hover:bg-surface-container-low">
                  <td class="p-md">
                    <div class="mb-1 font-label-md text-label-md text-on-background">{{ job.title }}</div>
                    <div class="font-body-sm text-body-sm text-on-surface-variant">{{ job.meta }}</div>
                  </td>
                  <td class="p-md">
                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 font-label-sm text-label-sm" :class="job.statusClass">
                      <span class="h-1.5 w-1.5 rounded-full" :class="job.dotClass"></span>
                      {{ job.status }}
                    </span>
                  </td>
                  <td class="p-md font-body-sm text-body-sm text-on-surface-variant">{{ job.date }}</td>
                  <td class="p-md text-right font-label-md text-label-md text-on-background">{{ job.applications }}</td>
                  <td class="p-md text-center">
                    <Button variant="ghost" size="icon" class="cursor-pointer rounded-md text-on-surface-variant transition-colors hover:bg-surface-variant hover:text-primary">
                      <MoreHorizontal class="h-5 w-5" aria-hidden="true" />
                    </Button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="flex items-center justify-between border-t border-outline-variant p-md font-body-sm text-body-sm text-on-surface-variant">
            <span>Showing 1 to 3 of 12 listings</span>
            <div class="flex gap-2">
              <Button variant="outline" class="cursor-pointer rounded-md border-outline-variant px-3 py-1 hover:bg-surface-variant" disabled>
                Prev
              </Button>
              <Button variant="outline" class="cursor-pointer rounded-md border-outline-variant px-3 py-1 hover:bg-surface-variant">
                Next
              </Button>
            </div>
          </div>
        </section>
      </div>
    </main>
  </div>
</template>
