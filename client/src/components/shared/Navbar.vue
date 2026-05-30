<script setup lang="ts">
import { computed, ref } from 'vue';
import { Bell, LogOut, Settings } from 'lucide-vue-next';
import { RouterLink, useRouter } from 'vue-router';
import { Button } from '../ui/button';
import { useQueryClient } from '@tanstack/vue-query';
import { useProfile } from '@/Hooks/useProfile';
import { logoutApi } from '@/api/auth';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { BarChart3 } from '@lucide/vue';

type Profile = {
  name?: string;
  email?: string;
  role?: 'admin' | 'candidate' | 'employer';
  avatar_url?: string | null;
};

const isLoggedIn = ref(Boolean(localStorage.getItem('token')));
const queryClient = useQueryClient();
const router = useRouter();

const { data: profile } = useProfile(isLoggedIn.value);

const user = computed<Profile>(() => (profile.value ?? {}) as Profile);
const avatarUrl = computed(() => user.value.avatar_url || '');
const avatarInitial = computed(() => user.value.name?.charAt(0).toUpperCase());
const roleBasePath = computed(() => {
  if (user.value.role === 'admin') return '/admin';
  if (user.value.role === 'employer') return '/employee';

  return '/candidate';
});

function rolePath(page: 'dashboard' | 'notifications' | 'settings') {
  return `${roleBasePath.value}/${page}`;
}

const handleLogout = async () => {
  try {
    await logoutApi();
  } catch {
    // Local logout still succeeds if the token is already expired or the API is unavailable.
  }

  queryClient.clear();
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  isLoggedIn.value = false;
  router.push('/');
};
</script>

<template>
  <header class="sticky top-0 z-50 h-18 w-full border-b border-outline-variant bg-surface text-primary shadow-sm">
    <div class="mx-auto flex h-full w-full max-w-container-max items-center justify-between px-md">
      <div class="flex items-center gap-xl">
        <RouterLink to="/" class="cursor-pointer font-headline-md text-headline-md font-bold text-primary">WorkHive
        </RouterLink>

        <nav class="hidden items-center gap-md md:flex">
          <RouterLink
            class="cursor-pointer border-b-2 border-primary pb-1 text-primary transition-colors duration-200 hover:text-primary"
            to="/jobs">
            Find Jobs
          </RouterLink>
          <RouterLink
            class="cursor-pointer font-label-md text-label-md text-on-surface-variant transition-colors duration-200 hover:text-primary"
            to="/companies">
            Companies
          </RouterLink>
          <RouterLink
            class="cursor-pointer font-label-md text-label-md text-on-surface-variant transition-colors duration-200 hover:text-primary"
            to="/salaries">
            Salaries
          </RouterLink>
        </nav>
      </div>

      <div v-if="isLoggedIn" class="flex items-center gap-2">
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <Button
              variant="ghost"
              class="size-10 cursor-pointer overflow-hidden rounded-full border border-outline-variant bg-surface-container-lowest p-0 text-primary hover:bg-surface-container-low"
              type="button"
              aria-label="Open account menu"
            >
              <img
                v-if="avatarUrl"
                :src="avatarUrl"
                :alt="user.name"
                class="h-full w-full object-cover"
              />
              <span v-else class="flex h-full w-full items-center justify-center font-label-md text-label-md">
                {{ avatarInitial }}
              </span>
            </Button>
          </DropdownMenuTrigger>

          <DropdownMenuContent align="end" class="w-60">
            <DropdownMenuLabel>
              <div class="truncate font-label-md text-label-md text-on-surface">{{ user.name || 'Account' }}</div>
              <div class="truncate font-body-sm text-body-sm text-on-surface-variant">{{ user.email }}</div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />

            <DropdownMenuItem as-child>
              <RouterLink class="flex w-full cursor-pointer items-center gap-xs" :to="rolePath('dashboard')">
                <BarChart3 class="h-4 w-4" aria-hidden="true" />
                Dashboard
              </RouterLink>
            </DropdownMenuItem>
            <DropdownMenuItem as-child>
              <RouterLink class="flex w-full cursor-pointer items-center gap-xs" :to="rolePath('notifications')">
                <Bell class="h-4 w-4" aria-hidden="true" />
                Notifications
              </RouterLink>
            </DropdownMenuItem>
            <DropdownMenuItem as-child>
              <RouterLink class="flex w-full cursor-pointer items-center gap-xs" :to="rolePath('settings')">
                <Settings class="h-4 w-4" aria-hidden="true" />
                Settings
              </RouterLink>
            </DropdownMenuItem>

            <DropdownMenuSeparator />
            <DropdownMenuItem class="text-error focus:text-error" @select.prevent="handleLogout">
              <LogOut class="h-4 w-4" aria-hidden="true" />
              Logout
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
      <div v-else class="ml-sm hidden items-center gap-sm md:flex">
        <Button as-child
          class="cursor-pointer rounded border border-primary bg-transparent px-sm py-xs font-label-md text-label-md text-primary transition-colors hover:bg-surface-variant">
          <RouterLink class="cursor-pointer" to="/sign-in">Sign In</RouterLink>
        </Button>
        <Button as-child
          class="cursor-pointer rounded bg-primary-container px-sm py-xs font-label-md text-label-md text-on-primary transition-colors hover:bg-primary">
          <RouterLink class="cursor-pointer" to="/role-select">Sign Up</RouterLink>
        </Button>
      </div>
    </div>
  </header>
</template>
