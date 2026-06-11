<script setup lang="ts">
import { computed } from "vue";
import { useQuery } from "@tanstack/vue-query";
import { Briefcase, MapPin, DollarSign, Building2 } from "lucide-vue-next";
import { RouterLink } from "vue-router";
import { Button } from "../ui/button";
import { getJobsApi, type JobListingPublic } from "@/api/jobs";

const { data, isPending } = useQuery({
  queryKey: ["featured-jobs"],
  queryFn: () => getJobsApi({ per_page: 3 }),
});

const jobs = computed<JobListingPublic[]>(() => data.value?.data ?? []);

function formatSalary(min?: number | null, max?: number | null) {
  if (!min && !max) return null;
  const fmt = (n: number) =>
    n >= 1000 ? `$${(n / 1000).toFixed(0)}k` : `$${n}`;
  if (min && max) return `${fmt(min)} – ${fmt(max)}`;
  if (min) return `From ${fmt(min)}`;
  return `Up to ${fmt(max!)}`;
}
</script>

<template>
  <section class="bg-surface-container-low py-xl">
    <div class="mx-auto max-w-container-max px-md">
      <div class="mb-lg flex items-end justify-between">
        <div>
          <h2 class="font-headline-lg text-headline-lg text-on-surface">Featured Opportunities</h2>
          <p class="mt-xs font-body-md text-body-md text-on-surface-variant">
            Hand-picked roles from top companies.
          </p>
        </div>
        <RouterLink class="cursor-pointer font-label-md text-label-md text-primary hover:underline" to="/jobs">
          View all jobs
        </RouterLink>
      </div>

      <div v-if="isPending" class="grid grid-cols-1 gap-md md:grid-cols-3">
        <div v-for="i in 3" :key="i" class="animate-pulse rounded-xl border border-outline-variant bg-surface-container-lowest p-md">
          <div class="mb-3 h-5 w-3/4 rounded bg-surface-container-low" />
          <div class="mb-2 h-4 w-1/2 rounded bg-surface-container-low" />
          <div class="h-4 w-full rounded bg-surface-container-low" />
        </div>
      </div>

      <div v-else-if="!jobs.length" class="text-center p-lg text-on-surface-variant">
        No featured opportunities at the moment.
      </div>

      <div v-else class="grid grid-cols-1 gap-md md:grid-cols-3">
        <article
          v-for="job in jobs"
          :key="job.id"
          class="hover-lift soft-shadow flex flex-col justify-between rounded-xl border border-outline-variant/20 bg-surface-container-lowest p-md transition duration-200"
        >
          <div>
            <div class="mb-md flex items-start justify-between">
              <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 text-xl font-bold text-primary">
                {{ job.employer_profile?.company_name?.charAt(0) || "?" }}
              </div>
              <span class="rounded-full bg-secondary-container/20 px-xs py-0.5 font-label-sm text-label-sm text-on-secondary-container capitalize">
                {{ job.work_type }}
              </span>
            </div>

            <h3 class="mb-xs font-headline-md text-headline-md text-on-surface line-clamp-1">
              {{ job.title }}
            </h3>
            <div class="mb-sm font-body-md text-body-md text-on-surface-variant flex items-center gap-1.5">
              <Building2 class="h-4 w-4 text-on-surface-variant" />
              <span>{{ job.employer_profile?.company_name || "Company" }}</span>
              <span v-if="job.location" class="hidden sm:inline">• {{ job.location }}</span>
            </div>
            <p class="mb-md line-clamp-2 text-xs text-on-surface-variant leading-relaxed">
              {{ job.description }}
            </p>
          </div>

          <div class="flex items-center justify-between border-t border-outline-variant pt-sm mt-auto">
            <div class="font-label-md text-label-md text-on-surface">
              {{ formatSalary(job.salary_min, job.salary_max) || "N/A" }}
            </div>
            <Button
              as-child
              class="cursor-pointer rounded bg-primary-container px-sm py-xs font-label-md text-label-md text-on-primary transition-colors hover:bg-primary"
            >
              <RouterLink class="cursor-pointer" :to="`/jobs/${job.id}`">Details</RouterLink>
            </Button>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>
