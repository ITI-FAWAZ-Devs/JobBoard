<script setup lang="ts">
import { ArrowRight, BriefcaseBusiness } from 'lucide-vue-next';
import { RouterLink } from 'vue-router';
import { Button } from '../ui/button';
</script>

<template>
  <section class="bg-surface py-xl">
    <div class="mx-auto max-w-container-max px-md">
      <div class="grid grid-cols-1 gap-xl md:grid-cols-2">
        <article class="relative flex min-h-100 flex-col justify-center overflow-hidden rounded-xl bg-inverse-surface p-xl text-inverse-on-surface">
          <div
            class="absolute inset-0 bg-cover bg-center opacity-20"
            style="background-image: url('https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&q=80&w=800')"
          ></div>
          <div class="relative z-10">
            <div class="mb-md inline-block rounded-full bg-primary-container px-sm py-xs font-label-sm text-label-sm text-on-primary-container">
              For Candidates
            </div>
            <h2 class="mb-sm font-headline-lg text-headline-lg text-surface-container-lowest">
              Accelerate Your Career
            </h2>
            <p class="mb-lg font-body-lg text-body-lg opacity-90">
              Build a standout profile, get matched with ideal roles, and connect directly with
              hiring managers at top companies.
            </p>
            <Button
              as-child
              class="inline-flex cursor-pointer items-center gap-xs rounded bg-primary-container px-lg py-sm font-label-md text-label-md text-on-primary transition-colors hover:bg-primary"
            >
              <RouterLink class="cursor-pointer" to="/role-select">
                Create Profile <ArrowRight class="h-4 w-4" aria-hidden="true" />
              </RouterLink>
            </Button>
          </div>
        </article>

        <article class="relative flex min-h-100 flex-col justify-center overflow-hidden rounded-xl border border-outline-variant bg-surface-container p-xl text-on-surface">
          <div
            class="absolute inset-0 bg-cover bg-center opacity-10"
            style="background-image: url('https://images.unsplash.com/photo-1553877522-43269d4ea984?auto=format&fit=crop&q=80&w=800')"
          ></div>
          <div class="relative z-10">
            <div class="mb-md inline-block rounded-full bg-surface-variant px-sm py-xs font-label-sm text-label-sm text-on-surface-variant">
              For Employers
            </div>
            <h2 class="mb-sm font-headline-lg text-headline-lg">Hire Top Talent Faster</h2>
            <p class="mb-lg font-body-lg text-body-lg text-on-surface-variant">
              Access a curated pool of verified professionals. Use our advanced matching tools to
              find the perfect fit for your team.
            </p>
            <Button
              as-child
              class="inline-flex cursor-pointer items-center gap-xs rounded border-2 border-primary bg-transparent px-lg py-sm font-label-md text-label-md text-primary transition-colors hover:bg-surface-variant"
            >
              <RouterLink class="cursor-pointer" to="/post-job">
                Post a Job <BriefcaseBusiness class="h-4 w-4" aria-hidden="true" />
              </RouterLink>
            </Button>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>
