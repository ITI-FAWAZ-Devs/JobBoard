<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import {
  BarChart3,
  BriefcaseBusiness,
  FileText,
  Heart,
  Mail,
  Settings,
  Shield,
  User,
  Users,
} from 'lucide-vue-next';
import Sidebar from '@/components/shared/Sidebar.vue';
import { useAuth } from '@/composables/useAuth';

const route = useRoute();
const { user } = useAuth();

const navItemsBySection = {
  admin: [
    { label: 'Dashboard', icon: BarChart3, to: '/admin/dashboard' },
    { label: 'Users', icon: Users, to: '/admin/users' },
    { label: 'Moderation', icon: Shield, to: '/admin/moderation' },
    { label: 'Settings', icon: Settings, to: '/admin/settings' },
  ],
  candidate: [
    { label: 'Dashboard', icon: BarChart3, to: '/candidate/dashboard' },
    { label: 'Browse Jobs', icon: BriefcaseBusiness, to: '/candidate/jobs' },
    { label: 'Applications', icon: FileText, to: '/candidate/applications' },
    { label: 'Profile', icon: User, to: '/candidate/profile' },
    { label: 'Saved Jobs', icon: Heart, to: '/candidate/saved' },
    { label: 'Settings', icon: Settings, to: '/candidate/settings' },
  ],
  employee: [
    { label: 'Dashboard', icon: BarChart3, to: '/employee/dashboard' },
    { label: 'Applications', icon: BriefcaseBusiness, to: '/employee/applications' },
    { label: 'Job Postings', icon: BriefcaseBusiness, to: '/employee/jobs' },
    { label: 'Analytics', icon: BarChart3, to: '/employee/analytics' },
    { label: 'Messages', icon: Mail, to: '/employee/messages' },
    { label: 'Profile', icon: User, to: '/employee/profile' },
    { label: 'Settings', icon: Settings, to: '/employee/settings' },
  ],
} as const;

const section = computed<keyof typeof navItemsBySection>(() => {
  if (route.path.startsWith('/admin')) return 'admin';
  if (route.path.startsWith('/employee')) return 'employee';

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