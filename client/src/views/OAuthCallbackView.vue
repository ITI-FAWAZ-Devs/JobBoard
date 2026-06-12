<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { toast } from 'vue-sonner';
import { Loader2 } from 'lucide-vue-next';
import { useAuth } from '@/composables/useAuth';

const route = useRoute();
const router = useRouter();
const { fetchUserProfile, user } = useAuth();

const isConnectMode = computed(() => route.query.mode === 'connect');
const statusText = computed(() => isConnectMode.value ? 'Connecting your LinkedIn profile...' : 'Signing you in...');

function getRoleHomePath(role?: string | null) {
  if (role === 'admin') return '/admin/dashboard';
  if (role === 'employer') return '/employer/dashboard';
  if (role === 'candidate') return '/candidate/dashboard';
  return '/';
}

onMounted(async () => {
  // Profile-connect mode: no new token, just reload profile and redirect back
  if (isConnectMode.value) {
    try {
      await fetchUserProfile();
      if (user.value) {
        localStorage.setItem('user', JSON.stringify(user.value));
      }
      toast.success('LinkedIn profile connected successfully!');
    } catch {
      toast.error('Failed to refresh profile after connecting LinkedIn.');
    }
    router.replace('/candidate/profile');
    return;
  }

  const token = typeof route.query.token === 'string' ? route.query.token : null;

  if (!token) {
    toast.error('Social sign-in failed. Please try again.');
    router.replace('/sign-in');
    return;
  }

  localStorage.setItem('token', token);

  try {
    await fetchUserProfile();
    if (user.value) {
      localStorage.setItem('user', JSON.stringify(user.value));
    }
    toast.success('Logged in successfully.');
    router.replace(getRoleHomePath(user.value?.role));
  } catch {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    toast.error('Social sign-in failed. Please try again.');
    router.replace('/sign-in');
  }
});
</script>

<template>
  <main class="flex min-h-screen items-center justify-center bg-gradient-mesh text-on-surface">
    <div class="flex flex-col items-center gap-sm">
      <Loader2 class="h-8 w-8 animate-spin text-primary" aria-hidden="true" />
      <p class="font-body-md text-body-md text-on-surface-variant">{{ statusText }}</p>
    </div>
  </main>
</template>
