<script setup lang="ts">
import { computed } from "vue";
import { useQuery } from "@tanstack/vue-query";
import { Clock, Flag, Users } from "lucide-vue-next";
import { RouterLink } from "vue-router";
import { Button } from "@/components/ui/button";
import {
  getAdminDashboardApi,
  type AdminDashboard,
} from "@/api/admin";

const { data } = useQuery({
  queryKey: ["admin", "dashboard"],
  queryFn: () => getAdminDashboardApi(),
});

const dashboard = computed<AdminDashboard | null>(() => data.value?.data ?? null);
</script>

<template>
  <div class="min-h-screen bg-background text-on-background">
    <main class="mx-auto w-full max-w-container-max flex-1 p-md md:p-lg">
      <div class="mb-xl flex flex-col justify-between gap-sm md:flex-row md:items-center">
        <div>
          <h1 class="font-headline-lg text-headline-lg text-on-background">Platform Overview</h1>
          <p class="mt-1 font-body-md text-body-md text-on-surface-variant">
            Monitor activity and manage content across WorkHive.
          </p>
        </div>
        <Button variant="outline" size="sm">Export Report</Button>
      </div>

      <div class="mb-xl grid grid-cols-1 gap-md md:grid-cols-4">
        <article class="flex flex-col justify-between rounded-xl bg-surface-container-lowest p-md shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
          <div class="mb-sm flex items-start justify-between">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-surface-container text-primary">
              <Clock class="h-5 w-5" aria-hidden="true" />
            </div>
          </div>
          <div>
            <p class="mb-1 font-label-md text-label-md text-on-surface-variant">Pending Approvals</p>
            <h3 class="font-headline-xl text-headline-xl text-on-background">{{ dashboard?.jobs.pending ?? '—' }}</h3>
          </div>
        </article>

        <article class="flex flex-col justify-between rounded-xl bg-surface-container-lowest p-md shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
          <div class="mb-sm flex items-start justify-between">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-surface-container text-secondary">
              <Users class="h-5 w-5" aria-hidden="true" />
            </div>
          </div>
          <div>
            <p class="mb-1 font-label-md text-label-md text-on-surface-variant">Total Users</p>
            <h3 class="font-headline-xl text-headline-xl text-on-background">{{ dashboard?.users.total ?? '—' }}</h3>
            <p class="font-body-xs text-body-xs text-on-surface-variant">
              {{ dashboard?.users.employers ?? '—' }} employers · {{ dashboard?.users.candidates ?? '—' }} candidates
            </p>
          </div>
        </article>

        <article class="flex flex-col justify-between rounded-xl bg-surface-container-lowest p-md shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
          <div class="mb-sm flex items-start justify-between">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-surface-container text-destructive">
              <Flag class="h-5 w-5" aria-hidden="true" />
            </div>
          </div>
          <div>
            <p class="mb-1 font-label-md text-label-md text-on-surface-variant">Flagged Comments</p>
            <h3 class="font-headline-xl text-headline-xl text-on-background">{{ dashboard?.flagged_comments_count ?? '—' }}</h3>
          </div>
        </article>

        <article class="flex flex-col justify-between rounded-xl bg-surface-container-lowest p-md shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
          <div class="mb-sm flex items-start justify-between">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-surface-container text-primary">
              <Clock class="h-5 w-5" aria-hidden="true" />
            </div>
          </div>
          <div>
            <p class="mb-1 font-label-md text-label-md text-on-surface-variant">Jobs Breakdown</p>
            <h3 class="font-headline-xl text-headline-xl text-on-background">{{ dashboard?.jobs.total ?? '—' }}</h3>
            <p class="font-body-xs text-body-xs text-on-surface-variant">
              {{ dashboard?.jobs.approved ?? '—' }} approved · {{ dashboard?.jobs.rejected ?? '—' }} rejected
            </p>
          </div>
        </article>
      </div>

      <div class="grid gap-lg lg:grid-cols-[minmax(0,1fr)_320px]">
        <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
          <div class="flex items-center justify-between border-b border-outline-variant p-md">
            <h2 class="font-headline-md text-headline-md text-on-background">Recent Pending Jobs</h2>
            <RouterLink
              class="font-label-md text-label-md text-primary hover:underline"
              to="/admin/jobs"
            >
              View All
            </RouterLink>
          </div>

          <div v-if="!dashboard?.recent_pending_jobs?.length" class="p-md font-body-sm text-body-sm text-on-surface-variant">
            No pending jobs right now.
          </div>
          <div v-else class="divide-y divide-outline-variant">
            <div v-for="job in dashboard.recent_pending_jobs" :key="job.id" class="p-md">
              <div class="flex flex-wrap items-center justify-between gap-md">
                <div>
                  <p class="font-label-md text-label-md text-on-background">{{ job.title }}</p>
                  <p class="font-body-sm text-body-sm text-on-surface-variant">
                    {{ job.company_name || 'Unknown company' }}
                    <span v-if="job.location"> - {{ job.location }}</span>
                  </p>
                </div>
                <span class="font-body-xs text-body-xs text-on-surface-variant">{{ job.created_at }}</span>
              </div>
            </div>
          </div>
        </section>

        <div class="grid gap-lg">
          <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
            <div class="flex items-center gap-xs border-b border-outline-variant p-md">
              <Flag class="h-4 w-4 text-destructive" aria-hidden="true" />
              <h2 class="font-headline-md text-headline-md text-on-background">Flagged Comments</h2>
            </div>

            <div v-if="!dashboard?.recent_flagged_comments?.length" class="p-md font-body-sm text-body-sm text-on-surface-variant">
              No flagged comments.
            </div>
            <div v-else class="divide-y divide-outline-variant">
              <div v-for="comment in dashboard.recent_flagged_comments" :key="comment.id" class="p-md">
                <div class="flex items-center justify-between font-body-xs text-body-xs text-on-surface-variant">
                  <span>{{ comment.user_name }}</span>
                  <span>{{ comment.created_at }}</span>
                </div>
                <p class="mt-sm font-body-sm text-body-sm text-on-background">
                  "{{ comment.content }}"
                </p>
                <p class="mt-xs font-body-xs text-body-xs text-on-surface-variant">
                  on {{ comment.job_title }}
                </p>
                <div class="mt-sm flex items-center gap-sm">
                  <RouterLink
                    to="/admin/comments"
                    class="font-label-sm text-label-sm text-primary hover:underline"
                  >
                    Manage Comments
                  </RouterLink>
                </div>
              </div>
            </div>
          </section>
        </div>
      </div>
    </main>
  </div>
</template>
