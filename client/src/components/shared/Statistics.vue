<script setup lang="ts">
import { computed } from "vue";
import { useQuery } from "@tanstack/vue-query";
import { getPublicStatisticsApi } from "@/api/jobs";

const { data, isPending } = useQuery({
  queryKey: ["public-statistics"],
  queryFn: getPublicStatisticsApi,
});

const stats = computed(() => data.value?.data ?? { jobs_count: 0, candidates_count: 0, companies_count: 0 });
</script>

<template>
  <section class="bg-surface py-xl">
    <div class="mx-auto max-w-container-max px-md">
      <div class="grid grid-cols-1 gap-lg text-center md:grid-cols-3">
        <div class="flex flex-col items-center">
          <div class="mb-xs font-headline-xl text-headline-xl text-primary">
            {{ isPending ? "..." : stats.jobs_count }}
          </div>
          <div class="font-headline-sm text-headline-sm text-on-surface">Jobs Posted</div>
          <div class="mt-xs font-body-sm text-body-sm text-on-surface-variant">
            Active opportunities updated daily.
          </div>
        </div>

        <div class="flex flex-col items-center">
          <div class="mb-xs font-headline-xl text-headline-xl text-secondary">
            {{ isPending ? "..." : stats.candidates_count }}
          </div>
          <div class="font-headline-sm text-headline-sm text-on-surface">Candidates</div>
          <div class="mt-xs font-body-sm text-body-sm text-on-surface-variant">
            Professionals building their careers.
          </div>
        </div>

        <div class="flex flex-col items-center">
          <div class="mb-xs font-headline-xl text-headline-xl text-tertiary">
            {{ isPending ? "..." : stats.companies_count }}
          </div>
          <div class="font-headline-sm text-headline-sm text-on-surface">Companies</div>
          <div class="mt-xs font-body-sm text-body-sm text-on-surface-variant">
            Top tier employers hiring now.
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
