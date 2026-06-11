<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { MapPin, Search } from 'lucide-vue-next';
import { RouterLink } from 'vue-router';
import { Button } from '../ui/button';

const router = useRouter();
const searchQuery = ref('');
const location = ref('');

function triggerSearch() {
  const query: Record<string, string> = {};
  if (searchQuery.value.trim()) {
    query.q = searchQuery.value.trim();
  }
  if (location.value.trim()) {
    query.location = location.value.trim();
  }
  router.push({ path: '/jobs', query });
}
</script>

<template>
  <section class="relative overflow-hidden bg-surface-container-low px-md py-xl">
    <div
      class="pointer-events-none absolute inset-0 opacity-10"
      style="background-image: radial-gradient(circle at 50% 50%, #2563eb 0%, transparent 50%)"
    ></div>

    <div class="relative z-10 mx-auto flex max-w-container-max flex-col items-center text-center">
      <h1 class="mb-sm max-w-3xl font-headline-xl text-headline-xl text-on-surface">
        Find Your Next Big Opportunity
      </h1>
      <p class="mb-lg max-w-2xl font-body-lg text-body-lg text-on-surface-variant">
        Connect with top employers and discover roles that align with your career goals in a premium
        marketplace designed for professionals.
      </p>

      <div class="glass-panel soft-shadow mb-xl flex w-full max-w-4xl flex-col items-center gap-sm rounded-xl p-sm md:flex-row">
        <div class="relative w-full flex-1">
          <Search class="absolute left-sm top-1/2 h-5 w-5 -translate-y-1/2 text-outline" aria-hidden="true" />
          <input
            v-model="searchQuery"
            @keyup.enter="triggerSearch"
            class="w-full rounded border border-outline-variant bg-surface-container-lowest py-xs pl-xl pr-sm font-body-md text-body-md text-on-surface focus:border-primary focus:ring-1 focus:ring-primary"
            placeholder="Job Title, Keyword, or Company"
            type="text"
          />
        </div>

        <div class="relative w-full md:w-1/3">
          <MapPin class="absolute left-sm top-1/2 h-5 w-5 -translate-y-1/2 text-outline" aria-hidden="true" />
          <input
            v-model="location"
            @keyup.enter="triggerSearch"
            class="w-full rounded border border-outline-variant bg-surface-container-lowest py-xs pl-xl pr-sm font-body-md text-body-md text-on-surface focus:border-primary focus:ring-1 focus:ring-primary"
            placeholder="Location"
            type="text"
          />
        </div>

        <Button
          @click="triggerSearch"
          class="flex h-10.5 w-full cursor-pointer items-center justify-center whitespace-nowrap rounded bg-primary-container px-lg py-xs font-label-md text-label-md text-on-primary transition-colors hover:bg-primary md:w-auto"
          type="button"
        >
          Search Jobs
        </Button>
      </div>

      <div class="flex flex-wrap justify-center gap-sm">
        <RouterLink class="cursor-pointer rounded-full bg-secondary-container/20 px-sm py-[4px] font-label-sm text-label-sm text-on-secondary-container transition-colors hover:bg-secondary-container/30" to="/jobs?category=software-development">Software Engineering</RouterLink>
        <RouterLink class="cursor-pointer rounded-full bg-secondary-container/20 px-sm py-[4px] font-label-sm text-label-sm text-on-secondary-container transition-colors hover:bg-secondary-container/30" to="/jobs?category=data-science">Data Science</RouterLink>
        <RouterLink class="cursor-pointer rounded-full bg-secondary-container/20 px-sm py-[4px] font-label-sm text-label-sm text-on-secondary-container transition-colors hover:bg-secondary-container/30" to="/jobs?category=design">Design</RouterLink>
        <RouterLink class="cursor-pointer rounded-full bg-secondary-container/20 px-sm py-[4px] font-label-sm text-label-sm text-on-secondary-container transition-colors hover:bg-secondary-container/30" to="/jobs?category=marketing">Marketing</RouterLink>
      </div>
    </div>
  </section>
</template>

