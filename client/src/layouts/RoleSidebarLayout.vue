<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import {
  BarChart3,
  BriefcaseBusiness,
  ClipboardCheck,
  CreditCard,
  FileText,
  Flag,
  Heart,
  LayoutDashboard,
  Mail,
  Settings,
  User,
  Users,
} from 'lucide-vue-next';
import Sidebar from '@/components/shared/Sidebar.vue';
import { useAuth } from '@/composables/useAuth';

const route = useRoute();
const { user } = useAuth();

const navItemsBySection = {
  admin: [
    { label: 'Dashboard', icon: LayoutDashboard, to: '/admin/dashboard' },
    { label: 'Pending Jobs', icon: ClipboardCheck, to: '/admin/jobs' },
    { label: 'User Management', icon: Users, to: '/admin/users' },
    { label: 'Comments', icon: Flag, to: '/admin/comments' },
    { label: 'Profile', icon: User, to: '/admin/profile' },
    { label: 'Settings', icon: Settings, to: '/admin/settings' },
  ],
  candidate: [
    { label: 'Dashboard', icon: BarChart3, to: '/candidate/dashboard' },
    { label: 'Browse Jobs', icon: BriefcaseBusiness, to: '/jobs' },
    { label: 'Applications', icon: FileText, to: '/candidate/applications' },
    { label: 'Profile', icon: User, to: '/candidate/profile' },
    { label: 'Saved Jobs', icon: Heart, to: '/candidate/saved' },
    { label: 'Settings', icon: Settings, to: '/candidate/settings' },
  ],
  employer: [
    { label: 'Dashboard', icon: BarChart3, to: '/employer/dashboard' },
    { label: 'Post Job', icon: BriefcaseBusiness, to: '/employer/jobs/create' },
    { label: 'Applications', icon: ClipboardCheck, to: '/employer/applications' },
    { label: 'Analytics', icon: BarChart3, to: '/employer/analytics' },
    { label: 'Checkout', icon: CreditCard, to: '/employer/checkout' },
    { label: 'Profile', icon: User, to: '/employer/profile' },
    { label: 'Settings', icon: Settings, to: '/employer/settings' },
  ],
} as const;

const section = computed<keyof typeof navItemsBySection>(() => {
  if (route.path.startsWith('/admin')) return 'admin';
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
