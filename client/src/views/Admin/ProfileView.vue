<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Edit3, Loader2, Mail, Shield, Calendar } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { useProfile } from '@/Hooks/useProfile';
import { updateProfileApi } from '@/api/profile';
import type { UserProfile } from '@/types/profile';

const { data: profile, refetch } = useProfile();

const user = computed<UserProfile>(() => (profile.value ?? {}) as UserProfile);

const isEditing = ref(false);
const isSaving = ref(false);

const form = reactive({ name: '', email: '' });
const avatarFile = ref<File | null>(null);
const avatarPreview = ref('');

watch(user, (u) => {
  form.name = u.name || '';
  form.email = u.email || '';
  avatarPreview.value = u.avatar_url || '';
}, { immediate: true });

function handleAvatarChange(e: Event) {
  const input = e.target as HTMLInputElement;
  avatarFile.value = input.files?.[0] ?? null;
  if (avatarFile.value) avatarPreview.value = URL.createObjectURL(avatarFile.value);
}

function cancelEdit() {
  const u = user.value;
  form.name = u.name || '';
  form.email = u.email || '';
  avatarPreview.value = u.avatar_url || '';
  avatarFile.value = null;
  isEditing.value = false;
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
  if (avatarFile.value) payload.append('avatar', avatarFile.value);

  isSaving.value = true;
  try {
    await updateProfileApi(payload);
    await refetch();
    avatarFile.value = null;
    isEditing.value = false;
    toast.success('Profile updated.');
  } catch (error) {
    toast.error(getErrorMessage(error));
  } finally {
    isSaving.value = false;
  }
}

const joinedDate = computed(() => {
  const d = (user.value as any)?.created_at;
  if (!d) return '—';
  return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
});
</script>

<template>
  <div class="min-h-screen bg-background text-on-background">
    <main class="mx-auto w-full max-w-container-max flex-1 p-md md:p-lg">
      <div class="mb-xl">
        <h1 class="font-headline-lg text-headline-lg text-on-background">My Profile</h1>
        <p class="mt-1 font-body-md text-body-md text-on-surface-variant">Manage your administrator account.</p>
      </div>

      <div class="grid gap-lg lg:grid-cols-[320px_1fr]">
        <aside class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
          <div class="flex flex-col items-center p-lg text-center">
            <div class="mb-md flex h-28 w-28 items-center justify-center overflow-hidden rounded-full bg-surface-variant">
              <img v-if="avatarPreview" :src="avatarPreview" alt="Avatar" class="h-full w-full object-cover" />
              <span v-else class="font-headline-xl text-headline-xl text-on-surface-variant">{{ user.name?.charAt(0).toUpperCase() || 'A' }}</span>
            </div>

            <label v-if="isEditing" class="mb-md cursor-pointer rounded-lg border border-outline-variant px-4 py-2 font-label-md text-label-md text-on-surface hover:bg-surface-variant">
              Change Avatar
              <input class="hidden" accept="image/*" type="file" @change="handleAvatarChange" />
            </label>

            <h2 class="font-headline-md text-headline-md text-on-background">{{ user.name || 'Administrator' }}</h2>

            <div class="mt-xs flex items-center gap-1 font-label-sm text-label-sm text-on-surface-variant">
              <Shield class="h-4 w-4" aria-hidden="true" />
              Administrator
            </div>

            <div class="mt-sm flex items-center gap-1 font-body-xs text-body-xs text-on-surface-variant">
              <Calendar class="h-3.5 w-3.5" aria-hidden="true" />
              Member since {{ joinedDate }}
            </div>
          </div>
        </aside>

        <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
          <div class="flex items-center justify-between border-b border-outline-variant p-md">
            <h2 class="font-headline-md text-headline-md text-on-background">Account Information</h2>
            <Button v-if="!isEditing" variant="ghost" size="icon" class="text-on-surface-variant hover:text-primary" @click="isEditing = true">
              <Edit3 class="h-5 w-5" aria-hidden="true" />
            </Button>
          </div>

          <div class="grid gap-md p-md">
            <div>
              <label class="mb-1 block font-label-sm text-label-sm text-on-surface-variant">Full Name</label>
              <div v-if="!isEditing" class="font-body-md text-body-md text-on-background">{{ user.name || '—' }}</div>
              <input v-else v-model="form.name" class="w-full rounded-lg border border-outline-variant bg-surface px-4 py-3 font-body-md text-body-md text-on-background outline-none focus:border-primary" type="text" />
            </div>

            <div>
              <label class="mb-1 block font-label-sm text-label-sm text-on-surface-variant">
                <Mail class="mr-1 inline h-4 w-4" aria-hidden="true" />
                Email Address
              </label>
              <div v-if="!isEditing" class="font-body-md text-body-md text-on-background">{{ user.email || '—' }}</div>
              <input v-else v-model="form.email" class="w-full rounded-lg border border-outline-variant bg-surface px-4 py-3 font-body-md text-body-md text-on-background outline-none focus:border-primary" type="email" />
            </div>
          </div>

          <div v-if="isEditing" class="flex items-center justify-end gap-sm border-t border-outline-variant p-md">
            <Button variant="outline" :disabled="isSaving" @click="cancelEdit">Cancel</Button>
            <Button :disabled="isSaving" @click="handleSave">
              <Loader2 v-if="isSaving" class="mr-2 h-4 w-4 animate-spin" />
              {{ isSaving ? 'Saving...' : 'Save Changes' }}
            </Button>
          </div>
        </section>
      </div>
    </main>
  </div>
</template>
