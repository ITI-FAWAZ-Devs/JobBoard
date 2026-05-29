<script setup lang="ts">
import { RouterLink } from 'vue-router';
</script>

<template>
  <footer class="w-full border-t border-outline-variant bg-surface-container-highest px-md py-xl font-body-sm text-body-sm text-primary">
    <div class="mx-auto flex w-full max-w-container-max flex-col items-center justify-between gap-md md:flex-row">
      <RouterLink class="font-headline-sm text-headline-sm font-bold text-on-surface" to="/">WorkHive</RouterLink>

      <nav class="flex flex-wrap justify-center gap-md">
        <RouterLink class="cursor-pointer font-label-sm text-label-sm text-on-surface-variant underline decoration-2 underline-offset-4 transition-colors hover:text-primary" to="/privacy-policy">Privacy Policy</RouterLink>
        <RouterLink class="cursor-pointer font-label-sm text-label-sm text-on-surface-variant underline decoration-2 underline-offset-4 transition-colors hover:text-primary" to="/terms-of-service">Terms of Service</RouterLink>
        <RouterLink class="cursor-pointer font-label-sm text-label-sm text-on-surface-variant underline decoration-2 underline-offset-4 transition-colors hover:text-primary" to="/cookie-policy">Cookie Policy</RouterLink>
        <RouterLink class="cursor-pointer font-label-sm text-label-sm text-on-surface-variant underline decoration-2 underline-offset-4 transition-colors hover:text-primary" to="/contact">Contact Us</RouterLink>
      </nav>

      <div class="font-label-sm text-label-sm text-on-surface-variant">
        © 2026 WorkHive Inc. All rights reserved.
      </div>
    </div>
  </footer>
</template>
