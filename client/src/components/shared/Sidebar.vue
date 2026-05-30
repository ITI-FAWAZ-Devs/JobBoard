<script setup lang="ts">
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { RouterLink } from 'vue-router';
import { CircleHelp, LogOut } from 'lucide-vue-next';
import { logoutApi } from '@/api/auth';
import { useAuth } from '@/composables/useAuth';
import type { UserProfile } from '@/types/profile';

interface NavItem {
  label: string;
  icon: any;
  to: string;
  active?: boolean;
}

const props = defineProps<{
  navItems: NavItem[];
  profile?: UserProfile | null;
}>();

const displayName = computed(() => {
  return props.profile?.profile?.company_name?.trim() || props.profile?.name?.trim() || 'WorkHive';
});

const displayRole = computed(() => {
  if (props.profile?.role === 'admin') return 'Administrator';
  if (props.profile?.role === 'employer') return 'Employer';
  if (props.profile?.role === 'candidate') return 'Candidate';

});

const plan = computed(() => {
  return 'Free Plan';
});

const avatarUrl = computed(() => {
  return props.profile?.avatar_url;
});

const avatarInitial = computed(() => displayName.value.charAt(0).toUpperCase());

const router = useRouter();
const { logout } = useAuth();

async function handleLogout() {
  try {
    await logoutApi();
  } catch {
    // proceed even if API fails
  }
  logout();
  router.push('/');
}
</script>

<template>
  <aside class="sticky top-0 z-20 hidden h-screen w-64 shrink-0 flex-col border-r border-outline-variant bg-surface-container-low py-md md:flex">
    <div class="mb-lg px-md">
      <div class="mb-xs flex items-center gap-2">
        <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-surface-variant">
          <img v-if="avatarUrl" :alt="displayName" class="h-full w-full object-cover" :src="avatarUrl" />
          <span v-else class="font-label-md text-label-md text-on-surface-variant">{{ avatarInitial }}</span>
        </div>
        <div>
          <h2 class="font-headline-sm text-headline-sm font-bold text-primary">{{ displayName }}</h2>
          <p class="font-label-sm text-label-sm text-on-surface-variant">{{ displayRole }}</p>
        </div>
      </div>
      <Button
        variant="outline"
        class="mt-sm w-full cursor-pointer rounded-lg border-primary px-sm py-xs font-label-md text-label-md text-primary transition-colors hover:bg-surface-variant"
      >
        Upgrade Plan
      </Button>
    </div>

    <nav class="flex-1 space-y-xs overflow-y-auto px-sm">
      <RouterLink
        v-for="item in navItems"
        :key="item.label"
        class="flex cursor-pointer items-center gap-xs rounded-lg px-sm py-xs transition-all duration-150"
        :class="
          item.active
            ? 'translate-x-1 bg-primary-container text-on-primary-container'
            : 'text-on-surface-variant hover:bg-surface-variant'
        "
        :to="item.to"
      >
        <component :is="item.icon" class="h-5 w-5" aria-hidden="true" />
        <span class="font-label-md text-label-md">{{ item.label }}</span>
      </RouterLink>
    </nav>

    <div class="mt-auto space-y-xs border-t border-outline-variant px-sm pt-md">
      <RouterLink class="flex cursor-pointer items-center gap-xs rounded-lg px-sm py-xs text-on-surface-variant transition-all hover:bg-surface-variant" to="/help">
        <CircleHelp class="h-5 w-5" aria-hidden="true" />
        <span class="font-label-md text-label-md">Help Center</span>
      </RouterLink>
      <button class="flex w-full cursor-pointer items-center gap-xs rounded-lg px-sm py-xs text-on-surface-variant transition-all hover:bg-surface-variant" @click="handleLogout">
        <LogOut class="h-5 w-5" aria-hidden="true" />
        <span class="font-label-md text-label-md">Logout</span>
      </button>
    </div>
  </aside>
</template>
