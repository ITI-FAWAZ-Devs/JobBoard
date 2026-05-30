<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import {
  BarChart3,
  BriefcaseBusiness,
  CreditCard,
  FileText,
  Heart,
  Mail,
  Settings,
  User,
} from 'lucide-vue-next';
import Sidebar from '@/components/shared/Sidebar.vue';
import { useAuth } from '@/composables/useAuth';

const route = useRoute();
const { user } = useAuth();

const navItemsBySection = {
  candidate: [
    { label: 'Dashboard', icon: BarChart3, to: '/candidate/dashboard' },
    { label: 'Browse Jobs', icon: BriefcaseBusiness, to: '/candidate/jobs' },
    { label: 'Applications', icon: FileText, to: '/candidate/applications' },
    { label: 'Profile', icon: User, to: '/candidate/profile' },
    { label: 'Saved Jobs', icon: Heart, to: '/candidate/saved' },
    { label: 'Settings', icon: Settings, to: '/candidate/settings' },
  ],
  employer: [
    { label: 'Dashboard', icon: BarChart3, to: '/employer/dashboard' },
    { label: 'Analytics', icon: BarChart3, to: '/employer/analytics' },
    { label: 'Checkout', icon: CreditCard, to: '/employer/checkout' },
    { label: 'Profile', icon: User, to: '/employer/profile' },
    { label: 'Messages', icon: Mail, to: '/employer/messages' },
    { label: 'Settings', icon: Settings, to: '/employer/settings' },
  ],
} as const;

const section = computed<keyof typeof navItemsBySection>(() => {
  if (route.path.startsWith('/employer')) return 'employer';

  return 'candidate';
});

const navItems = computed(() => {
  return navItemsBySection[section.value].map((item) => ({
    ...item,
    active: route.path === item.to || route.path.startsWith(`${item.to}/`),
  }));
});
</script>

<template>
  <div class="min-h-screen bg-background text-on-background md:flex">
    <Sidebar :navItems="navItems" :profile="user" />
    <div class="min-h-screen flex-1">
      <RouterView />
    </div>
  </div>
</template>
