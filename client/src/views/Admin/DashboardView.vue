<script setup lang="ts">
import { Bell, Menu } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
</script>

<template>
  <div class="min-h-screen bg-background text-on-background" >
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
        <div class="mb-xl">
          <h1 class="font-headline-lg text-headline-lg text-on-background">Admin Dashboard</h1>
          <p class="mt-xs font-body-md text-body-md text-on-surface-variant">Platform overview and moderation tools.</p>
        </div>
      </div>
    </main>
  </div>
</template>
