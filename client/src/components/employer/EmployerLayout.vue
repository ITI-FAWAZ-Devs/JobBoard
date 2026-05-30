<script setup lang="ts">
import { computed } from "vue";
import { RouterLink, useRoute } from "vue-router";
import {
  Briefcase,
  FileText,
  LayoutDashboard,
  MessagesSquare,
  TrendingUp,
  CircleHelp,
  Settings,
} from "lucide-vue-next";
import { Button } from "@/components/ui/button";

const props = defineProps<{
  title: string;
  subtitle?: string;
}>();

const route = useRoute();

const navItems = computed(() => [
  { label: "Dashboard", to: "/employer", icon: LayoutDashboard },
  { label: "Applications", to: "/employer/applications", icon: FileText },
  { label: "Job Postings", to: "/employer/jobs", icon: Briefcase },
  { label: "Analytics", to: "/employer/analytics", icon: TrendingUp },
  { label: "Messages", to: "/employer/messages", icon: MessagesSquare },
]);

const bottomNavItems = computed(() => [
  { label: "Help Center", to: "/employer/help", icon: CircleHelp },
  { label: "Settings", to: "/employer/settings", icon: Settings },
]);

const isActive = (path: string) => {
  // exact match or child route
  if (path === '/employer') {
    return route.path === '/employer';
  }
  return route.path.startsWith(path);
};
</script>

<template>
  <div class="min-h-screen bg-gray-50 text-gray-900 font-sans">
    <div class="flex min-h-screen">
      <!-- Sidebar -->
      <aside class="fixed inset-y-0 left-0 z-20 flex w-64 flex-col border-r border-gray-200 bg-[#F8FAFC]">
        
        <!-- Header Profile Area -->
        <div class="px-6 py-6">
          <div class="flex items-center gap-3 mb-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white shadow-sm ring-1 ring-gray-200">
              <div class="h-3 w-3 rounded-full bg-blue-600"></div>
            </div>
            <div>
              <div class="font-bold text-blue-800 text-base leading-tight">Recruiter Pro</div>
              <div class="text-xs text-gray-500 font-medium">Enterprise Plan</div>
            </div>
          </div>
          
          <Button variant="outline" class="w-full border-blue-600 text-blue-600 hover:bg-blue-50 bg-transparent font-medium py-2 h-auto rounded-lg shadow-sm">
            Upgrade Plan
          </Button>
        </div>

        <!-- Main Navigation -->
        <nav class="flex-1 px-4 space-y-1">
          <RouterLink
            v-for="item in navItems"
            :key="item.label"
            :to="item.to"
            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
            :class="isActive(item.to) ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
          >
            <component :is="item.icon" class="h-5 w-5 shrink-0" aria-hidden="true" />
            {{ item.label }}
          </RouterLink>
        </nav>

        <!-- Bottom Navigation -->
        <div class="border-t border-gray-200 p-4">
          <nav class="space-y-1">
            <RouterLink
              v-for="item in bottomNavItems"
              :key="item.label"
              :to="item.to"
              class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900"
            >
              <component :is="item.icon" class="h-5 w-5 shrink-0" aria-hidden="true" />
              {{ item.label }}
            </RouterLink>
          </nav>
        </div>
      </aside>

      <!-- Main Content Area -->
      <div class="ml-64 flex min-h-screen flex-1 flex-col">
        <header class="px-8 pb-4 pt-8">
          <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
              <h1 class="text-3xl font-bold text-gray-900 tracking-tight">{{ props.title }}</h1>
              <p v-if="props.subtitle" class="mt-1 text-base text-gray-500">
                {{ props.subtitle }}
              </p>
            </div>
            <div class="flex items-center gap-3">
              <slot name="actions" />
            </div>
          </div>
        </header>

        <main class="flex-1 px-8 pb-8">
          <slot />
        </main>
      </div>
    </div>
  </div>
</template>
