<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { toast } from 'vue-sonner';
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

watch(
  user,
  (currentUser) => {
    form.name = currentUser.name || '';
    form.email = currentUser.email || '';
    avatarPreview.value = currentUser.avatar_url || '';
  },
  { immediate: true },
);

function handleAvatarChange(event: Event) {
  const input = event.target as HTMLInputElement;
  avatarFile.value = input.files?.[0] ?? null;

  if (avatarFile.value) {
    avatarPreview.value = URL.createObjectURL(avatarFile.value);
  }
}

function getErrorMessage(error: unknown) {
  const response = (error as {
    response?: {
      data?: {
        message?: string;
        errors?: Record<string, string[]>;
      };
    };
  })?.response?.data;

  const firstValidationError = response?.errors ? Object.values(response.errors)[0]?.[0] : undefined;

  return firstValidationError || response?.message || 'Failed to update your profile.';
}

async function handleSave() {
  const payload = new FormData();

  payload.append('name', form.name);
  payload.append('email', form.email);

  if (avatarFile.value) {
    payload.append('avatar', avatarFile.value);
  }

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
    toast.success('Account updated successfully.');
  } catch (error) {
    toast.error(getErrorMessage(error));
  }
}
</script>

<template>
  <main class="min-h-screen bg-surface p-4 text-on-surface md:p-8">
    <div class="mx-auto max-w-5xl space-y-6">
      <section class="rounded-2xl border border-outline-variant/50 bg-surface-container p-6 shadow-sm">
        <h1 class="font-headline-lg text-headline-lg">Candidate Settings</h1>
        <p class="mt-1 font-body-md text-body-md text-on-surface-variant">
          Update your name, email, and password.
        </p>
      </section>

      <form class="grid gap-6 lg:grid-cols-[280px_1fr]" @submit.prevent="handleSave">
        <aside class="rounded-2xl border border-outline-variant/50 bg-surface-container p-6 shadow-sm">
          <div class="flex flex-col items-center text-center">
            <div class="mb-4 flex h-28 w-28 items-center justify-center overflow-hidden rounded-full border-4 border-white bg-surface-container-lowest shadow-sm">
              <img v-if="avatarPreview" :src="avatarPreview" alt="Profile preview" class="h-full w-full object-cover" />
              <span v-else class="font-headline-xl text-headline-xl text-primary">{{ user.name?.charAt(0).toUpperCase() || 'C' }}</span>
            </div>

            <h2 class="font-headline-md text-headline-md text-on-surface">{{ user.name || 'Candidate' }}</h2>
            <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">{{ user.email || 'your.email@example.com' }}</p>

            <label class="mt-4 inline-flex cursor-pointer items-center justify-center rounded-lg border border-outline-variant px-4 py-2 font-label-md text-label-md text-on-surface transition-colors hover:bg-surface">
              Change Avatar
              <input class="hidden" accept="image/*" type="file" @change="handleAvatarChange" />
            </label>
          </div>
        </aside>

        <section class="space-y-6">
          <div class="rounded-2xl border border-outline-variant/50 bg-surface-container p-6 shadow-sm">
            <h2 class="mb-4 font-headline-md text-headline-md text-on-surface">Account Information</h2>

            <div class="grid gap-4 md:grid-cols-2">
              <label class="grid gap-2">
                <span class="font-label-sm text-label-sm text-on-surface-variant">Full Name</span>
                <input v-model="form.name" class="rounded-lg border border-outline-variant bg-surface px-4 py-3 text-on-surface outline-none focus:border-primary" type="text" />
              </label>

              <label class="grid gap-2">
                <span class="font-label-sm text-label-sm text-on-surface-variant">Email Address</span>
                <input v-model="form.email" class="rounded-lg border border-outline-variant bg-surface px-4 py-3 text-on-surface outline-none focus:border-primary" type="email" />
              </label>
            </div>
          </div>

          <div class="rounded-2xl border border-outline-variant/50 bg-surface-container p-6 shadow-sm">
            <h2 class="mb-4 font-headline-md text-headline-md text-on-surface">Security</h2>

            <div class="grid gap-4 md:grid-cols-2">
              <label class="grid gap-2">
                <span class="font-label-sm text-label-sm text-on-surface-variant">New Password</span>
                <input v-model="form.password" class="rounded-lg border border-outline-variant bg-surface px-4 py-3 text-on-surface outline-none focus:border-primary" type="password" />
              </label>

              <label class="grid gap-2">
                <span class="font-label-sm text-label-sm text-on-surface-variant">Confirm Password</span>
                <input
                  v-model="form.passwordConfirmation"
                  class="rounded-lg border border-outline-variant bg-surface px-4 py-3 text-on-surface outline-none focus:border-primary"
                  type="password"
                />
              </label>
            </div>
          </div>

          <div class="flex justify-end gap-3">
            <Button as-child variant="outline" class="rounded-lg border-outline-variant px-5 py-3">
              <RouterLink to="/candidate/dashboard">Cancel</RouterLink>
            </Button>
            <Button class="rounded-lg bg-primary px-5 py-3 text-white hover:bg-primary/90" :disabled="isPending" type="submit">
              {{ isPending ? 'Saving...' : 'Save Changes' }}
            </Button>
          </div>
        </section>
      </form>
    </div>
  </main>
</template>
