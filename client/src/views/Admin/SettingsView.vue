<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { toast } from 'vue-sonner';
import { Loader2, Shield } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { useProfile } from '@/Hooks/useProfile';
import { updateProfileApi } from '@/api/profile';
import type { UserProfile } from '@/types/profile';

const { data: profile, isPending, refetch } = useProfile();

const user = computed<UserProfile>(() => (profile.value ?? {}) as UserProfile);

const form = reactive({
  name: '',
  email: '',
  password: '',
  passwordConfirmation: '',
});

const avatarFile = ref<File | null>(null);
const avatarPreview = ref('');

watch(user, (u) => {
  form.name = u.name || '';
  form.email = u.email || '';
  avatarPreview.value = u.avatar_url || '';
}, { immediate: true });

function handleAvatarChange(event: Event) {
  const input = event.target as HTMLInputElement;
  avatarFile.value = input.files?.[0] ?? null;
  if (avatarFile.value) avatarPreview.value = URL.createObjectURL(avatarFile.value);
}

function getErrorMessage(error: unknown) {
  const response = (error as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })?.response?.data;
  const firstErr = response?.errors ? Object.values(response.errors)[0]?.[0] : undefined;
  return firstErr || response?.message || 'Failed to update.';
}

async function handleSave() {
  const payload = new FormData();
  payload.append('name', form.name);
  payload.append('email', form.email);
  payload.append('_method', 'PATCH');

  if (avatarFile.value) payload.append('avatar', avatarFile.value);

  if (form.password.trim()) {
    payload.append('password', form.password);
    payload.append('password_confirmation', form.passwordConfirmation);
  }

  try {
    await updateProfileApi(payload);
    await refetch();
    avatarFile.value = null;
    form.password = '';
    form.passwordConfirmation = '';
    toast.success('Settings updated.');
  } catch (error) {
    toast.error(getErrorMessage(error));
  }
}
</script>

<template>
  <div class="min-h-screen bg-background text-on-background">
    <main class="mx-auto w-full max-w-container-max flex-1 p-md md:p-lg">
      <div class="mb-xl">
        <h1 class="font-headline-lg text-headline-lg text-on-background">Settings</h1>
        <p class="mt-1 font-body-md text-body-md text-on-surface-variant">
          Update your admin account details.
        </p>
      </div>

      <form class="grid gap-lg lg:grid-cols-[280px_1fr]" @submit.prevent="handleSave">
        <aside class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
          <div class="flex flex-col items-center p-lg text-center">
            <div class="mb-md flex h-28 w-28 items-center justify-center overflow-hidden rounded-full bg-surface-variant">
              <img v-if="avatarPreview" :src="avatarPreview" alt="Avatar" class="h-full w-full object-cover" />
              <span v-else class="font-headline-xl text-headline-xl text-on-surface-variant">{{ user.name?.charAt(0).toUpperCase() || 'A' }}</span>
            </div>

            <label class="cursor-pointer rounded-lg border border-outline-variant px-4 py-2 font-label-md text-label-md text-on-surface hover:bg-surface-variant">
              Change Avatar
              <input class="hidden" accept="image/*" type="file" @change="handleAvatarChange" />
            </label>

            <h2 class="mt-md font-headline-md text-headline-md text-on-background">{{ user.name || 'Admin' }}</h2>
            <div class="mt-xs flex items-center gap-1 font-label-sm text-label-sm text-on-surface-variant">
              <Shield class="h-4 w-4" aria-hidden="true" />
              Administrator
            </div>
            <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">{{ user.email }}</p>
          </div>
        </aside>

        <div class="grid gap-lg">
          <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
            <div class="border-b border-outline-variant p-md">
              <h2 class="font-headline-md text-headline-md text-on-background">Account Information</h2>
            </div>
            <div class="grid gap-md p-md md:grid-cols-2">
              <label class="grid gap-2">
                <span class="font-label-sm text-label-sm text-on-surface-variant">Full Name</span>
                <input
                  v-model="form.name"
                  class="rounded-lg border border-outline-variant bg-surface px-4 py-3 font-body-md text-body-md text-on-background outline-none focus:border-primary"
                  type="text"
                />
              </label>
              <label class="grid gap-2">
                <span class="font-label-sm text-label-sm text-on-surface-variant">Email Address</span>
                <input
                  v-model="form.email"
                  class="rounded-lg border border-outline-variant bg-surface px-4 py-3 font-body-md text-body-md text-on-background outline-none focus:border-primary"
                  type="email"
                />
              </label>
            </div>
          </section>

          <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
            <div class="border-b border-outline-variant p-md">
              <h2 class="font-headline-md text-headline-md text-on-background">Security</h2>
            </div>
            <div class="grid gap-md p-md md:grid-cols-2">
              <label class="grid gap-2">
                <span class="font-label-sm text-label-sm text-on-surface-variant">New Password</span>
                <input
                  v-model="form.password"
                  class="rounded-lg border border-outline-variant bg-surface px-4 py-3 font-body-md text-body-md text-on-background outline-none focus:border-primary"
                  type="password"
                  placeholder="Leave blank to keep current"
                />
              </label>
              <label class="grid gap-2">
                <span class="font-label-sm text-label-sm text-on-surface-variant">Confirm Password</span>
                <input
                  v-model="form.passwordConfirmation"
                  class="rounded-lg border border-outline-variant bg-surface px-4 py-3 font-body-md text-body-md text-on-background outline-none focus:border-primary"
                  type="password"
                  placeholder="Repeat new password"
                />
              </label>
            </div>
          </section>

          <div class="flex justify-end gap-sm">
            <Button as-child variant="outline">
              <RouterLink to="/admin/profile">Cancel</RouterLink>
            </Button>
            <Button type="submit" :disabled="isPending">
              <Loader2 v-if="isPending" class="mr-2 h-4 w-4 animate-spin" />
              {{ isPending ? 'Saving...' : 'Save Changes' }}
            </Button>
          </div>
        </div>
      </form>
    </main>
  </div>
</template>
