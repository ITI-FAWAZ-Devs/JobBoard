<script setup lang="ts">
import { computed } from "vue";
import { RouterLink, useRoute } from "vue-router";
import {
  LayoutDashboard,
  ClipboardCheck,
  Users,
  HelpCircle,
  Settings,
} from "lucide-vue-next";

const props = defineProps<{
  title: string;
  subtitle?: string;
}>();

const route = useRoute();

const navItems = computed(() => [
  {
    label: "Dashboard",
    to: "/admin",
    icon: LayoutDashboard,
  },
  {
    label: "Pending Jobs",
    to: "/admin/pending-jobs",
    icon: ClipboardCheck,
  },
  {
    label: "User Management",
    to: "/admin/users",
    icon: Users,
  },
]);

const isActive = (path: string) => route.path === path;
</script>

<template>
  <div class="min-h-screen bg-surface text-on-surface">
    <div class="flex min-h-screen">
      <aside class="fixed inset-y-0 left-0 z-20 flex w-72 flex-col border-r border-outline-variant bg-surface-container-low px-lg py-lg">
        <div class="mb-lg space-y-[2px]">
          <div class="text-lg font-semibold text-primary">WorkHive</div>
          <div class="text-xs font-medium uppercase tracking-wide text-on-surface-variant">Admin Console</div>
        </div>

        <nav class="flex flex-col gap-xs">
          <RouterLink
            v-for="item in navItems"
            :key="item.label"
            :to="item.to"
            class="flex items-center gap-sm rounded-lg px-sm py-xs text-sm font-medium text-on-surface-variant transition"
            :class="isActive(item.to)
              ? 'bg-primary/10 text-primary'
              : 'hover:bg-surface-container-lowest'"
          >
            <component :is="item.icon" class="h-4 w-4" aria-hidden="true" />
            {{ item.label }}
          </RouterLink>
        </nav>

        <div class="mt-auto space-y-xs pt-lg">
          <button
            class="flex w-full items-center gap-sm rounded-lg px-sm py-xs text-sm font-medium text-on-surface-variant transition hover:bg-surface-container-low"
            type="button"
          >
            <HelpCircle class="h-4 w-4" aria-hidden="true" />
            Help Center
          </button>
          <button
            class="flex w-full items-center gap-sm rounded-lg px-sm py-xs text-sm font-medium text-on-surface-variant transition hover:bg-surface-container-low"
            type="button"
          >
            <Settings class="h-4 w-4" aria-hidden="true" />
            Settings
          </button>
        </div>
      </aside>

      <div class="ml-72 flex min-h-screen flex-1 flex-col">
        <header class="px-lg pb-md pt-lg">
          <div class="flex flex-wrap items-center justify-between gap-md">
            <div>
              <h1 class="text-2xl font-semibold text-on-surface">{{ props.title }}</h1>
              <p v-if="props.subtitle" class="text-sm text-on-surface-variant">
                {{ props.subtitle }}
              </p>
            </div>
            <div class="flex items-center gap-sm">
              <slot name="actions" />
            </div>
          </div>
        </header>

        <main class="flex-1 px-lg pb-lg">
          <slot />
        </main>
      </div>
    </div>
  </div>
</template>
