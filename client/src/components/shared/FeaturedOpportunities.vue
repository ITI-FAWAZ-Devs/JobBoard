<script setup lang="ts">
import { Megaphone, Paintbrush, Rocket } from 'lucide-vue-next';
import { RouterLink } from 'vue-router';
import { Button } from '../ui/button';
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
        <RouterLink class="hidden cursor-pointer font-label-md text-label-md text-primary hover:underline md:block" to="/jobs">
          View all jobs
        </RouterLink>
      </div>

      <div class="grid grid-cols-1 gap-md md:grid-cols-3">
        <article
          class="hover-lift soft-shadow flex flex-col justify-between rounded-xl border border-outline-variant/20 bg-surface-container-lowest p-md md:col-span-2"
        >
          <div>
            <div class="mb-md flex items-start justify-between">
              <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-surface-container text-xl font-bold text-primary">
                <Rocket class="h-6 w-6" aria-hidden="true" />
              </div>
              <span class="rounded-full bg-secondary-container/20 px-xs py-0.5 font-label-sm text-label-sm text-on-secondary-container">
                Remote
              </span>
            </div>

            <h3 class="mb-xs font-headline-md text-headline-md text-on-surface">
              Senior Full Stack Engineer
            </h3>
            <div class="mb-sm font-body-md text-body-md text-on-surface-variant">
              TechNova Inc. • San Francisco, CA
            </div>
            <div class="mb-lg flex gap-sm">
              <span class="rounded bg-surface-variant px-xs py-0.5 font-label-sm text-label-sm text-on-surface-variant">React</span>
              <span class="rounded bg-surface-variant px-xs py-0.5 font-label-sm text-label-sm text-on-surface-variant">Node.js</span>
              <span class="rounded bg-surface-variant px-xs py-0.5 font-label-sm text-label-sm text-on-surface-variant">AWS</span>
            </div>
          </div>

          <div class="flex items-center justify-between border-t border-outline-variant pt-sm">
            <div class="font-label-md text-label-md text-on-surface">$140k - $180k / yr</div>
            <Button
              as-child
              class="cursor-pointer rounded bg-primary-container px-sm py-xs font-label-md text-label-md text-on-primary transition-colors hover:bg-primary"
            >
              <RouterLink class="cursor-pointer" to="/jobs/senior-full-stack-engineer">Apply Now</RouterLink>
            </Button>
          </div>
        </article>

        <article
          class="hover-lift soft-shadow flex flex-col justify-between rounded-xl border border-outline-variant/20 bg-surface-container-lowest p-md"
        >
          <div>
            <div class="mb-md flex items-start justify-between">
              <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-surface-container text-xl font-bold text-primary">
                <Paintbrush class="h-6 w-6" aria-hidden="true" />
              </div>
              <span class="rounded-full bg-surface-variant px-xs py-0.5 font-label-sm text-label-sm text-on-surface-variant">
                Hybrid
              </span>
            </div>
            <h3 class="mb-xs font-headline-sm text-headline-sm text-on-surface">Lead UX Designer</h3>
            <div class="mb-md font-body-sm text-body-sm text-on-surface-variant">
              Creative Solutions • New York, NY
            </div>
          </div>

          <div class="mt-auto flex items-center justify-between border-t border-outline-variant pt-sm">
            <div class="font-label-md text-label-md text-on-surface">$120k - $150k</div>
            <Button as-child variant="link" class="h-auto cursor-pointer p-0 font-label-md text-label-md text-primary">
              <RouterLink class="cursor-pointer" to="/jobs/lead-ux-designer">Details</RouterLink>
            </Button>
          </div>
        </article>

        <article
          class="hover-lift soft-shadow flex flex-col justify-between rounded-xl border border-outline-variant/20 bg-surface-container-lowest p-md"
        >
          <div>
            <div class="mb-md flex items-start justify-between">
              <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-surface-container text-xl font-bold text-primary">
                <Megaphone class="h-6 w-6" aria-hidden="true" />
              </div>
              <span class="rounded-full bg-secondary-container/20 px-xs py-0.5 font-label-sm text-label-sm text-on-secondary-container">
                Remote
              </span>
            </div>
            <h3 class="mb-xs font-headline-sm text-headline-sm text-on-surface">Director of Marketing</h3>
            <div class="mb-md font-body-sm text-body-sm text-on-surface-variant">
              GrowthStream • Austin, TX
            </div>
          </div>

          <div class="mt-auto flex items-center justify-between border-t border-outline-variant pt-sm">
            <div class="font-label-md text-label-md text-on-surface">$150k - $190k</div>
            <Button as-child variant="link" class="h-auto cursor-pointer p-0 font-label-md text-label-md text-primary">
              <RouterLink class="cursor-pointer" to="/jobs/director-of-marketing">Details</RouterLink>
            </Button>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>
