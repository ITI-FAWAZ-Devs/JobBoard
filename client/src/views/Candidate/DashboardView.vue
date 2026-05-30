<script setup lang="ts">
import { computed } from 'vue';
import {
  ArrowRight,
  Bell,
  Bookmark,
  ChevronRight,
  Eye,
  Lightbulb,
  Menu,
  Send,
  Sparkles,
  UserRound,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { RouterLink } from 'vue-router';
import { useProfile } from '@/Hooks/useProfile';
import type { UserProfile } from '@/types/profile';

const { data: profile } = useProfile();

const user = computed<UserProfile>(() => (profile.value ?? {}) as UserProfile);
const userName = computed(() => user.value.name?.trim() || 'Candidate');
const avatarUrl = computed(() => user.value.avatar_url || '');
const avatarInitial = computed(() => userName.value.charAt(0).toUpperCase());

const stats = [
  {
    label: 'Applied',
    value: '8',
    icon: Send,
    tone: 'bg-blue-50 border-blue-100 text-primary',
    iconTone: 'bg-blue-100',
  },
  {
    label: 'Saved',
    value: '12',
    icon: Bookmark,
    tone: 'bg-teal-50 border-teal-100 text-secondary',
    iconTone: 'bg-teal-100',
  },
  {
    label: 'Profile',
    value: '85% Complete',
    icon: Sparkles,
    tone: 'bg-violet-50 border-violet-100 text-violet-700',
    iconTone: 'bg-violet-100',
  },
];

const recommendedJobs = [
  {
    company: 'IBM',
    title: 'Senior Frontend Developer',
    meta: 'Remote',
    tags: ['React', 'TypeScript', '$120k - $150k'],
    accent: 'bg-slate-100 text-slate-800',
    cta: 'Apply Now',
  },
  {
    company: 'Google',
    title: 'UX/UI Designer',
    meta: 'London, UK',
    tags: ['Figma', 'Prototyping', 'Hybrid'],
    accent: 'bg-blue-100 text-blue-700',
    cta: 'Apply Now',
  },
  {
    company: 'Vercel',
    title: 'Product Manager',
    meta: 'San Francisco, CA',
    tags: ['Agile', 'SaaS', 'Remote'],
    accent: 'bg-black text-white',
    cta: 'Apply Now',
  },
  {
    company: 'Stripe',
    title: 'Backend Engineer',
    meta: 'New York, NY',
    tags: ['Node.js', 'PostgreSQL', 'On-site'],
    accent: 'bg-blue-600 text-white',
    cta: 'Apply Now',
  },
];

const applications = [
  { title: 'Senior UX Designer', company: 'Uber', date: 'Oct 24, 2023', status: 'Pending', statusClass: 'bg-blue-100 text-blue-800' },
  { title: 'Frontend Engineer', company: 'Spotify', date: 'Oct 20, 2023', status: 'Reviewed', statusClass: 'bg-violet-100 text-violet-800' },
  { title: 'Product Designer', company: 'Airbnb', date: 'Oct 15, 2023', status: 'Accepted', statusClass: 'bg-green-100 text-green-800' },
  { title: 'UI Developer', company: 'Meta', date: 'Oct 10, 2023', status: 'Rejected', statusClass: 'bg-red-100 text-red-800' },
];

const savedJobs = [
  { company: 'Amazon', title: 'Data Analyst', badge: 'A', badgeClass: 'bg-orange-100 text-orange-600' },
  { company: 'Microsoft', title: 'Cloud Architect', badge: 'M', badgeClass: 'bg-blue-100 text-blue-600' },
  { company: 'Netflix', title: 'Full Stack Dev', badge: 'N', badgeClass: 'bg-red-100 text-red-600' },
];

const activity = [
  {
    title: 'Google viewed your application',
    meta: '2 hours ago',
    icon: Eye,
    iconClass: 'bg-blue-100 text-primary',
  },
  {
    title: 'Application approved by Airbnb',
    meta: 'Yesterday',
    icon: UserRound,
    iconClass: 'bg-green-100 text-success',
  },
  {
    title: '5 new jobs match your profile',
    meta: '2 days ago',
    icon: Sparkles,
    iconClass: 'bg-violet-100 text-violet-600',
  },
];

</script>

<template>
  <div class="min-h-screen bg-surface text-on-surface">
    <main class="flex min-h-screen flex-1 flex-col overflow-hidden">
      <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-outline-variant bg-surface-container px-4 shadow-sm lg:hidden">
        <div class="flex items-center gap-3">
          <Button variant="ghost" size="icon" class="cursor-pointer text-on-surface-variant">
            <Menu class="h-5 w-5" aria-hidden="true" />
          </Button>
          <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-sm font-bold text-white">W</div>
        </div>
        <div class="flex items-center gap-3">
          <Button variant="ghost" size="icon" class="cursor-pointer text-on-surface-variant">
            <Bell class="h-5 w-5" aria-hidden="true" />
          </Button>
          <div class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-full border border-outline-variant bg-surface-container-lowest text-sm font-semibold text-primary">
            <img v-if="avatarUrl" :src="avatarUrl" :alt="userName" class="h-full w-full object-cover" />
            <span v-else>{{ avatarInitial }}</span>
          </div>
        </div>
      </header>

      <main class="flex-1 overflow-y-auto p-4 lg:p-8">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 xl:flex-row">
          <section class="flex-1 space-y-6">
            <div class="rounded-2xl border border-outline-variant/50 bg-surface-container p-6 shadow-sm">
              <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                  <h1 class="font-headline-lg text-headline-lg text-on-surface">Good morning, {{ userName }}!</h1>
                  <p class="mt-1 font-body-md text-body-md text-on-surface-variant">
                    Here is what's happening with your job search today.
                  </p>
                </div>
                <Button as-child class="self-start rounded-lg bg-primary px-4 py-2 font-label-md text-label-md text-white shadow-sm transition-colors hover:bg-primary/90 md:self-auto">
                  <RouterLink to="/candidate/profile">Edit Profile</RouterLink>
                </Button>
              </div>

              <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <article
                  v-for="stat in stats"
                  :key="stat.label"
                  class="flex items-center gap-4 rounded-xl border p-4 shadow-sm"
                  :class="stat.tone"
                >
                  <div class="flex h-12 w-12 items-center justify-center rounded-full" :class="stat.iconTone">
                    <component :is="stat.icon" class="h-5 w-5" aria-hidden="true" />
                  </div>
                  <div>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">{{ stat.label }}</p>
                    <p class="font-headline-md text-headline-md text-on-surface">{{ stat.value }}</p>
                  </div>
                </article>
              </div>
            </div>

            <section>
              <div class="mb-4 flex items-center justify-between">
                <h2 class="font-headline-md text-headline-md text-on-surface">Recommended Jobs</h2>
                <RouterLink class="font-label-md text-label-md text-primary hover:underline" to="/candidate/jobs">
                  See all
                </RouterLink>
              </div>

              <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <article
                  v-for="job in recommendedJobs"
                  :key="job.title"
                  class="rounded-xl border border-outline-variant/50 bg-surface-container p-5 shadow-sm transition-shadow hover:shadow-md"
                >
                  <div class="mb-4 flex items-start justify-between gap-3">
                    <div class="flex gap-3">
                      <div class="flex h-10 w-10 items-center justify-center rounded-lg font-bold" :class="job.accent">
                        {{ job.company.charAt(0) }}
                      </div>
                      <div>
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">{{ job.title }}</h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">{{ job.company }} • {{ job.meta }}</p>
                      </div>
                    </div>
                    <Button variant="ghost" size="icon" class="cursor-pointer text-on-surface-variant hover:text-secondary">
                      <Bookmark class="h-5 w-5" aria-hidden="true" />
                    </Button>
                  </div>

                  <div class="mb-4 flex flex-wrap gap-2">
                    <span
                      v-for="tag in job.tags"
                      :key="tag"
                      class="rounded-md border border-outline-variant bg-surface px-2.5 py-1 font-label-sm text-label-sm text-on-surface-variant"
                    >
                      {{ tag }}
                    </span>
                  </div>

                  <Button class="w-full rounded-lg bg-primary/10 py-2 font-label-md text-label-md text-primary transition-colors hover:bg-primary hover:text-white">
                    {{ job.cta }}
                  </Button>
                </article>
              </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-outline-variant/50 bg-surface-container shadow-sm">
              <div class="flex items-center justify-between border-b border-outline-variant p-5">
                <h2 class="font-headline-md text-headline-md text-on-surface">My Recent Applications</h2>
                <RouterLink class="font-label-md text-label-md text-primary hover:underline" to="/candidate/applications">
                  View History
                </RouterLink>
              </div>

              <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                  <thead>
                    <tr class="border-b border-outline-variant bg-surface text-xs uppercase tracking-wider text-on-surface-variant">
                      <th class="p-4 font-semibold">Job Title</th>
                      <th class="p-4 font-semibold">Company</th>
                      <th class="p-4 font-semibold">Date Applied</th>
                      <th class="p-4 font-semibold">Status</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-outline-variant text-sm">
                    <tr v-for="application in applications" :key="application.title" class="transition-colors hover:bg-surface/50">
                      <td class="p-4 font-medium text-on-surface">{{ application.title }}</td>
                      <td class="p-4 text-on-surface-variant">{{ application.company }}</td>
                      <td class="p-4 text-on-surface-variant">{{ application.date }}</td>
                      <td class="p-4">
                        <span class="inline-flex rounded-full px-2.5 py-0.5 font-medium" :class="application.statusClass">
                          {{ application.status }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </section>

            <section>
              <h2 class="mb-4 font-headline-md text-headline-md text-on-surface">Saved Jobs</h2>
              <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <article
                  v-for="job in savedJobs"
                  :key="job.title"
                  class="cursor-pointer rounded-xl border border-outline-variant/50 bg-surface-container p-4 text-center transition-colors hover:border-secondary"
                >
                  <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full font-bold" :class="job.badgeClass">
                    {{ job.badge }}
                  </div>
                  <p class="truncate font-headline-sm text-headline-sm text-on-surface">{{ job.title }}</p>
                  <p class="font-body-xs text-body-xs text-on-surface-variant">{{ job.company }}</p>
                </article>

                <RouterLink
                  class="flex flex-col items-center justify-center rounded-xl border border-dashed border-outline-variant bg-surface-container p-4 text-on-surface-variant transition-colors hover:border-primary hover:text-primary"
                  to="/candidate/saved"
                >
                  <ChevronRight class="mb-1 h-5 w-5" aria-hidden="true" />
                  <span class="font-label-md text-label-md">View All 12</span>
                </RouterLink>
              </div>
            </section>
          </section>

          <aside class="flex w-full flex-col gap-6 xl:w-80">
            <section class="relative overflow-hidden rounded-2xl border border-outline-variant/50 bg-surface-container p-6 text-center shadow-sm">
              <div class="absolute left-0 top-0 h-20 w-full bg-linear-to-r from-primary to-secondary"></div>
              <div class="relative z-10 mt-6">
                <div class="mx-auto mb-3 flex h-20 w-20 items-center justify-center overflow-hidden rounded-full border-4 border-white bg-surface-container-lowest shadow-sm">
                  <img v-if="avatarUrl" :src="avatarUrl" :alt="userName" class="h-full w-full object-cover" />
                  <span v-else class="font-headline-md text-headline-md text-primary">{{ avatarInitial }}</span>
                </div>
                <h3 class="font-headline-md text-headline-md text-on-surface">{{ userName }}</h3>
                <p class="mb-4 font-body-sm text-body-sm text-on-surface-variant">Open to opportunities</p>

                <div class="mb-4 flex flex-wrap justify-center gap-2">
                  <span class="rounded-md border border-outline-variant bg-surface px-2 py-1 font-label-sm text-label-sm text-on-surface-variant">
                    Remote
                  </span>
                  <span class="rounded-md border border-outline-variant bg-surface px-2 py-1 font-label-sm text-label-sm text-on-surface-variant">
                    Job Seeking
                  </span>
                </div>

                <Button as-child variant="outline" class="w-full rounded-lg border-outline-variant bg-white py-2 font-label-md text-label-md text-on-surface transition-colors hover:bg-surface">
                  <RouterLink to="/candidate/profile">Edit Profile</RouterLink>
                </Button>
              </div>
            </section>

            <section class="rounded-2xl border border-outline-variant/50 bg-surface-container p-5 shadow-sm">
              <div class="mb-4 flex items-center justify-between">
                <h3 class="font-headline-sm text-headline-sm text-on-surface">Recent Activity</h3>
                <span class="cursor-pointer font-label-sm text-label-sm text-primary">Mark all read</span>
              </div>

              <div class="space-y-4">
                <article v-for="item in activity" :key="item.title" class="relative flex gap-3">
                  <div v-if="item !== activity[activity.length - 1]" class="absolute left-3.75 top-8 bottom-[-16px] w-px bg-outline-variant"></div>
                  <div class="z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full" :class="item.iconClass">
                    <component :is="item.icon" class="h-4 w-4" aria-hidden="true" />
                  </div>
                  <div>
                    <p class="font-body-sm text-body-sm text-on-surface">{{ item.title }}</p>
                    <p class="mt-0.5 font-body-xs text-body-xs text-on-surface-variant">{{ item.meta }}</p>
                  </div>
                </article>
              </div>
            </section>

            <section class="rounded-2xl border border-teal-100 bg-linear-to-br from-teal-50 to-blue-50 p-5 shadow-sm">
              <div class="mb-3 flex items-center gap-2">
                <Lightbulb class="h-5 w-5 text-secondary" aria-hidden="true" />
                <h3 class="font-headline-sm text-headline-sm text-on-surface">Trending Tips</h3>
              </div>
              <h4 class="mb-2 font-label-md text-label-md text-on-surface">How to ace your technical interview in 2024</h4>
              <p class="mb-4 font-body-xs text-body-xs leading-relaxed text-on-surface-variant">
                Discover the top 5 strategies experts are using to pass algorithmic challenges and system design rounds.
              </p>
              <RouterLink class="flex items-center gap-1 font-label-md text-label-md text-secondary hover:text-teal-700" to="/candidate/tips">
                Read Article
                <ArrowRight class="h-4 w-4" aria-hidden="true" />
              </RouterLink>
            </section>
          </aside>
        </div>
      </main>
    </main>
  </div>
</template>
