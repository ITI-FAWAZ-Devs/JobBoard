<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import {
  Building,
  Edit3,
  Globe,
  Loader2,
  MapPin,
  Plus,
  Trash2,
  Users,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { useProfile } from '@/Hooks/useProfile';
import { updateProfileApi } from '@/api/profile';
import {
  createOfficeApi,
  updateOfficeApi,
  deleteOfficeApi,
  uploadGalleryPhotoApi,
  deleteGalleryPhotoApi,
} from '@/api/employer';
import type { UserProfile } from '@/types/profile';

const { data: profile, refetch: profileRefetch } = useProfile();

const user = computed<UserProfile>(() => (profile.value ?? {}) as UserProfile);

// Helper to get typed nested data from user
const eq = <T>(path: string, fallback: T): T => {
  const val = path.split('.').reduce((o: any, k) => o?.[k], user.value);
  return (val ?? fallback) as T;
};

const companyName = computed(() => eq('profile.company_name', 'Your Company'));
const industry = computed(() => eq('profile.industry', ''));
const website = computed(() => eq('profile.website', ''));
const employeeCount = computed(() => eq('profile.employee_count', ''));
const location = computed(() => eq('profile.location', ''));
const description = computed(() => eq('profile.description', ''));
const logoUrl = computed(() => eq('profile.logo_url', ''));
const coverPhotoUrl = computed(() => eq('profile.cover_photo_url', ''));
const perks = computed<any[]>(() => eq('profile.perks', []));
const offices = computed<any[]>(() => (user.value as any)?.offices ?? []);
const galleryPhotos = computed<any[]>(() => (user.value as any)?.gallery_photos ?? []);
const avatarUrl = computed(() => user.value.avatar_url || '');
const avatarInitial = computed(() => companyName.value.charAt(0).toUpperCase());

function handleCoverChange(e: Event) {
  const input = e.target as HTMLInputElement;
  const f = input.files?.[0];
  if (f) {
    coverFile.value = f;
    coverPreview.value = URL.createObjectURL(f);
  }
}

function handleLogoChange(e: Event) {
  const input = e.target as HTMLInputElement;
  const f = input.files?.[0];
  if (f) {
    logoFile.value = f;
    logoPreview.value = URL.createObjectURL(f);
  }
}

const isSaving = ref(false);
const isEditing = ref(false);

// Form state
const form = reactive({
  company_name: '',
  industry: '',
  website: '',
  employee_count: '',
  location: '',
  description: '',
  perks: '',
});

// Cover photo
const coverFile = ref<File | null>(null);
const coverPreview = ref('');

// Logo
const logoFile = ref<File | null>(null);
const logoPreview = ref('');

watch(
  user,
  (u) => {
    form.company_name = u.profile?.company_name || '';
    form.industry = (u.profile as any)?.industry || '';
    form.website = u.profile?.website || '';
    form.employee_count = (u.profile as any)?.employee_count || '';
    form.location = u.profile?.location || '';
    form.description = u.profile?.description || '';
    form.perks = ((u.profile as any)?.perks || []).join(', ');
  },
  { immediate: true },
);

function cancelEdit() {
  const u = user.value;
  form.company_name = u.profile?.company_name || '';
  form.industry = (u.profile as any)?.industry || '';
  form.website = u.profile?.website || '';
  form.employee_count = (u.profile as any)?.employee_count || '';
  form.location = u.profile?.location || '';
  form.description = u.profile?.description || '';
  form.perks = ((u.profile as any)?.perks || []).join(', ');
  coverFile.value = null;
  coverPreview.value = '';
  logoFile.value = null;
  logoPreview.value = '';
  editOffices.value = JSON.parse(JSON.stringify(offices.value));
  editGallery.value = JSON.parse(JSON.stringify(galleryPhotos.value));
  isEditing.value = false;
}

function getErrorMessage(error: unknown) {
  const response = (error as {
    response?: { data?: { message?: string; errors?: Record<string, string[]> } };
  })?.response?.data;
  const firstValidationError = response?.errors ? Object.values(response.errors)[0]?.[0] : undefined;
  return firstValidationError || response?.message || 'Failed to update.';
}

async function handleSave() {
  const payload = new FormData();
  payload.append('company_name', form.company_name);
  payload.append('industry', form.industry);
  payload.append('website', form.website);
  payload.append('employee_count', form.employee_count);
  payload.append('location', form.location);
  payload.append('description', form.description);

  const perksArr = form.perks
    .split(',')
    .map((s) => s.trim())
    .filter(Boolean);
  perksArr.forEach((perk) => payload.append('perks[]', perk));

  if (coverFile.value) payload.append('cover_photo', coverFile.value);
  if (logoFile.value) payload.append('logo', logoFile.value);

  isSaving.value = true;
  try {
    await updateProfileApi(payload);
    coverFile.value = null;
    coverPreview.value = '';
    logoFile.value = null;
    logoPreview.value = '';
    await profileRefetch();
    isEditing.value = false;
    toast.success('Profile updated.');
  } catch (error) {
    toast.error(getErrorMessage(error));
  } finally {
    isSaving.value = false;
  }
}

// Offline editing arrays for offices & gallery
const editOffices = ref<any[]>([]);
const newOfficeShow = ref(false);
const newOffice = reactive({ name: '', address: '', is_headquarters: false });
const officeSaving = ref<string>('');

function resetNewOffice() {
  newOffice.name = '';
  newOffice.address = '';
  newOffice.is_headquarters = false;
}

watch(offices, (o) => { editOffices.value = JSON.parse(JSON.stringify(o)); }, { immediate: true });

async function addOffice() {
  if (!newOffice.address) return;
  officeSaving.value = 'new';
  try {
    await createOfficeApi({ ...newOffice });
    await profileRefetch();
    resetNewOffice();
    newOfficeShow.value = false;
    toast.success('Office added.');
  } catch (e) {
    toast.error(getErrorMessage(e));
  } finally {
    officeSaving.value = '';
  }
}

async function saveOffice(item: any) {
  if (!item.address) return;
  officeSaving.value = item.id?.toString() ?? 'edit';
  try {
    if (item.id) {
      await updateOfficeApi(item.id, { name: item.name, address: item.address, is_headquarters: item.is_headquarters });
    }
    await profileRefetch();
    toast.success('Office updated.');
  } catch (e) {
    toast.error(getErrorMessage(e));
  } finally {
    officeSaving.value = '';
  }
}

async function removeOffice(id: number) {
  if (!confirm('Delete this office?')) return;
  try {
    await deleteOfficeApi(id);
    await profileRefetch();
    toast.success('Office deleted.');
  } catch (e) {
    toast.error(getErrorMessage(e));
  }
}

// Gallery
const editGallery = ref<any[]>([]);
const gallerySaving = ref<string>('');

watch(galleryPhotos, (g) => { editGallery.value = JSON.parse(JSON.stringify(g)); }, { immediate: true });

async function handleGalleryUpload(event: Event) {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];
  if (!file) return;
  gallerySaving.value = 'upload';
  try {
    await uploadGalleryPhotoApi(file);
    await profileRefetch();
    toast.success('Photo uploaded.');
  } catch (e) {
    toast.error(getErrorMessage(e));
  } finally {
    gallerySaving.value = '';
    input.value = '';
  }
}

async function removeGalleryPhoto(id: number) {
  if (!confirm('Delete this photo?')) return;
  try {
    await deleteGalleryPhotoApi(id);
    await profileRefetch();
    toast.success('Photo deleted.');
  } catch (e) {
    toast.error(getErrorMessage(e));
  }
}

// Profile strength indicator
const profileCompleteness = computed(() => {
  const filled = [
    form.company_name,
    form.industry,
    form.website,
    form.employee_count,
    form.location,
    form.description,
    coverPhotoUrl.value || coverFile.value,
    logoUrl.value || logoFile.value,
    perksArr.value.length,
    offices.value.length,
    galleryPhotos.value.length,
  ].filter(Boolean).length;
  return Math.min(100, Math.max(20, filled * 9));
});

const perksArr = computed(() => form.perks.split(',').map(s => s.trim()).filter(Boolean));
</script>

<template>
  <main class="min-h-screen bg-surface p-4 text-on-surface md:p-6 lg:p-8">
    <div class="mx-auto max-w-6xl">
      <!-- Cover Photo -->
      <div class="relative h-48 overflow-hidden rounded-xl bg-surface-container-high md:h-64">
        <img
          v-if="coverPreview || coverPhotoUrl"
          :src="coverPreview || coverPhotoUrl"
          alt="Cover"
          class="h-full w-full object-cover"
        />
        <div v-else class="flex h-full w-full items-center justify-center bg-gradient-to-br from-primary/10 to-secondary/10">
          <Building class="h-16 w-16 text-primary/30" aria-hidden="true" />
        </div>

        <label
          v-if="isEditing"
          class="absolute right-4 top-4 flex cursor-pointer items-center gap-2 rounded-lg bg-black/50 px-4 py-2 font-label-sm text-label-sm text-white backdrop-blur-sm hover:bg-black/60"
        >
          <Edit3 class="h-4 w-4" />
          Change Cover
          <input class="hidden" accept="image/*" type="file" @change="handleCoverChange" />
        </label>
      </div>

      <!-- Company Header -->
      <div class="relative -mt-16 flex flex-col items-center px-4 md:flex-row md:items-end md:gap-6 md:px-8">
        <div class="relative z-10 flex h-28 w-28 items-center justify-center overflow-hidden rounded-2xl border-4 border-surface bg-surface-container shadow-lg">
          <img
            v-if="logoPreview || logoUrl"
            :src="logoPreview || logoUrl"
            alt="Logo"
            class="h-full w-full object-cover"
          />
          <span v-else class="text-4xl font-bold text-primary">{{ avatarInitial }}</span>

          <label
            v-if="isEditing"
            class="absolute -bottom-1 -right-1 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full border-2 border-surface bg-primary text-white shadow hover:bg-primary/90"
          >
            <Edit3 class="h-4 w-4" />
            <input class="hidden" accept="image/*" type="file" @change="handleLogoChange" />
          </label>
        </div>

        <div class="mt-4 flex flex-1 flex-col items-center gap-2 text-center md:mt-0 md:items-start md:text-left">
          <div v-if="!isEditing" class="flex items-center gap-3">
            <h1 class="font-headline-lg text-headline-lg text-on-surface">{{ companyName }}</h1>
            <Button variant="ghost" size="icon" class="text-on-surface-variant hover:text-primary" @click="isEditing = true">
              <Edit3 class="h-5 w-5" />
            </Button>
          </div>
          <div v-else class="w-full">
            <input v-model="form.company_name" class="w-full rounded-lg border border-outline-variant bg-surface px-4 py-3 font-headline-md text-headline-md text-on-surface outline-none focus:border-primary" placeholder="Company Name" type="text" />
          </div>

          <div class="flex flex-wrap items-center justify-center gap-4 font-body-sm text-body-sm text-on-surface-variant md:justify-start">
            <span v-if="industry || isEditing" class="flex items-center gap-1">
              <Building class="h-4 w-4" aria-hidden="true" />
              <input v-if="isEditing" v-model="form.industry" class="rounded border border-outline-variant bg-surface px-2 py-1 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary" placeholder="Industry" type="text" />
              <span v-else>{{ industry }}</span>
            </span>
            <span v-if="website || isEditing" class="flex items-center gap-1">
              <Globe class="h-4 w-4" aria-hidden="true" />
              <input v-if="isEditing" v-model="form.website" class="rounded border border-outline-variant bg-surface px-2 py-1 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary" placeholder="https://example.com" type="url" />
              <a v-else :href="website" target="_blank" rel="noreferrer" class="text-primary hover:underline">{{ website }}</a>
            </span>
            <span v-if="form.employee_count || employeeCount || isEditing" class="flex items-center gap-1">
              <Users class="h-4 w-4" aria-hidden="true" />
              <input v-if="isEditing" v-model="form.employee_count" class="rounded border border-outline-variant bg-surface px-2 py-1 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary" placeholder="e.g. 51-200" type="text" />
              <span v-else>{{ employeeCount }} employees</span>
            </span>
            <span v-if="location || isEditing" class="flex items-center gap-1">
              <MapPin class="h-4 w-4" aria-hidden="true" />
              <input v-if="isEditing" v-model="form.location" class="rounded border border-outline-variant bg-surface px-2 py-1 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary" placeholder="City, Country" type="text" />
              <span v-else>{{ location }}</span>
            </span>
          </div>
        </div>
      </div>

      <!-- Bent Grid Layout -->
      <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Left Column -->
        <div class="flex flex-col gap-6 lg:col-span-2">
          <!-- About Company -->
          <div class="rounded-xl bg-surface-container p-6 shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
            <div class="mb-3 flex items-start justify-between">
              <h2 class="font-headline-sm text-headline-sm text-on-surface">About Company</h2>
              <Button v-if="!isEditing" variant="ghost" size="icon" class="text-on-surface-variant hover:text-primary" @click="isEditing = true">
                <Edit3 class="h-4 w-4" />
              </Button>
            </div>
            <p v-if="!isEditing" class="font-body-md text-body-md leading-relaxed text-on-surface-variant">
              {{ description || 'No description provided.' }}
            </p>
            <textarea
              v-else
              v-model="form.description"
              class="min-h-24 w-full rounded-lg border border-outline-variant bg-surface px-4 py-3 font-body-md text-body-md leading-relaxed text-on-surface outline-none focus:border-primary"
              placeholder="Tell job seekers about your company culture, mission, and values."
            ></textarea>
          </div>

          <!-- Culture Gallery -->
          <div class="rounded-xl bg-surface-container p-6 shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
            <div class="mb-4 flex items-center justify-between">
              <h2 class="font-headline-sm text-headline-sm text-on-surface">Culture Gallery</h2>
              <label v-if="isEditing" class="flex cursor-pointer items-center gap-1 font-label-sm text-label-sm text-primary hover:text-primary/80">
                <Plus class="h-4 w-4" />
                Add Photos
                <input class="hidden" accept="image/*" type="file" :disabled="gallerySaving === 'upload'" @change="handleGalleryUpload" />
              </label>
            </div>

            <div v-if="galleryPhotos.length" class="grid grid-cols-2 gap-4 md:grid-cols-3">
              <div v-for="photo in galleryPhotos" :key="photo.id" class="group relative overflow-hidden rounded-lg">
                <img :src="photo.photo_url" alt="Gallery" class="h-40 w-full object-cover transition-transform duration-300 group-hover:scale-105" />
                <button
                  v-if="isEditing"
                  class="absolute right-2 top-2 flex h-8 w-8 items-center justify-center rounded-full bg-black/50 text-white opacity-0 backdrop-blur-sm transition-opacity group-hover:opacity-100 hover:bg-error"
                  @click="removeGalleryPhoto(photo.id)"
                >
                  <Trash2 class="h-4 w-4" />
                </button>
              </div>
            </div>
            <div v-else class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-outline-variant bg-surface-container-low p-8">
              <Building class="mb-2 h-10 w-10 text-outline-variant" />
              <p class="font-label-md text-label-md text-on-surface-variant">No photos yet</p>
              <p class="font-body-sm text-body-sm text-outline">Add images that showcase your company culture.</p>
            </div>
          </div>

          <!-- Perks & Benefits -->
          <div class="rounded-xl bg-surface-container p-6 shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
            <div class="mb-4 flex items-center justify-between">
              <h2 class="font-headline-sm text-headline-sm text-on-surface">Perks &amp; Benefits</h2>
              <Button v-if="!isEditing" variant="ghost" size="icon" class="text-on-surface-variant hover:text-primary" @click="isEditing = true">
                <Edit3 class="h-4 w-4" />
              </Button>
            </div>

            <template v-if="!isEditing">
              <div v-if="perks.length" class="flex flex-wrap gap-2">
                <span
                  v-for="(perk, idx) in perks"
                  :key="idx"
                  class="rounded-lg border border-outline-variant bg-surface-container-low px-3 py-1.5 font-label-sm text-label-sm text-primary"
                >
                  {{ perk }}
                </span>
              </div>
              <p v-else class="font-body-sm text-body-sm text-on-surface-variant">No perks listed.</p>
            </template>

            <template v-else>
              <input
                v-model="form.perks"
                class="w-full rounded-lg border border-outline-variant bg-surface px-4 py-3 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary"
                placeholder="Remote-friendly, Health insurance, 401k matching, ..."
                type="text"
              />
              <p class="mt-2 font-label-sm text-label-sm text-outline">Comma-separated list of perks and benefits.</p>

              <div v-if="perksArr.length" class="mt-4 flex flex-wrap gap-2">
                <span
                  v-for="(perk, idx) in perksArr"
                  :key="idx"
                  class="rounded-lg border border-outline-variant bg-surface-container-low px-3 py-1.5 font-label-sm text-label-sm text-primary"
                >
                  {{ perk }}
                </span>
              </div>
            </template>
          </div>

          <!-- Office Locations -->
          <div class="rounded-xl bg-surface-container p-6 shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
            <div class="mb-4 flex items-center justify-between">
              <h2 class="font-headline-sm text-headline-sm text-on-surface">Office Locations</h2>
              <div class="flex items-center gap-1">
                <Button v-if="!isEditing" variant="ghost" size="icon" class="text-on-surface-variant hover:text-primary" @click="isEditing = true">
                  <Edit3 class="h-4 w-4" />
                </Button>
                <Button v-if="isEditing" variant="ghost" class="gap-1 font-label-sm text-label-sm text-primary hover:text-primary/80" @click="newOfficeShow = !newOfficeShow">
                  <Plus class="h-4 w-4" />
                  {{ newOfficeShow ? 'Cancel' : 'Add Office' }}
                </Button>
              </div>
            </div>

            <!-- Add new office form -->
            <div v-if="isEditing && newOfficeShow" class="mb-4 rounded-lg border border-primary/30 bg-surface-container-low p-4">
              <div class="grid gap-3 md:grid-cols-2">
                <input v-model="newOffice.name" class="rounded-lg border border-outline-variant bg-surface px-3 py-2 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary" placeholder="Office name (optional)" type="text" />
                <input v-model="newOffice.address" class="rounded-lg border border-outline-variant bg-surface px-3 py-2 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary" placeholder="Address *" type="text" />
              </div>
              <label class="mt-2 flex items-center gap-2 font-label-sm text-label-sm text-on-surface-variant">
                <input v-model="newOffice.is_headquarters" type="checkbox" class="h-4 w-4 rounded border-outline-variant" />
                Headquarters
              </label>
              <div class="mt-3 flex gap-2">
                <Button class="rounded-lg bg-primary px-4 py-1.5 font-label-sm text-label-sm text-white hover:bg-primary/90" :disabled="officeSaving === 'new'" @click="addOffice">
                  <Loader2 v-if="officeSaving === 'new'" class="mr-1 h-3 w-3 animate-spin" />
                  {{ officeSaving === 'new' ? 'Adding...' : 'Add' }}
                </Button>
                <Button variant="outline" class="rounded-lg border-outline-variant px-4 py-1.5 font-label-sm text-label-sm text-on-surface" @click="newOfficeShow = false; resetNewOffice()">Cancel</Button>
              </div>
            </div>

            <template v-if="!isEditing">
              <div v-if="offices.length" class="space-y-3">
                <div v-for="office in offices" :key="office.id" class="flex items-start gap-3 rounded-lg bg-surface-container-low p-4">
                  <div class="rounded-lg bg-primary/10 p-2 text-primary">
                    <MapPin class="h-5 w-5" />
                  </div>
                  <div class="flex-1">
                    <p class="font-label-md text-label-md text-on-surface">
                      {{ office.name || 'Office' }}
                      <span v-if="office.is_headquarters" class="ml-2 rounded bg-primary/10 px-2 py-0.5 font-label-sm text-label-sm text-primary">HQ</span>
                    </p>
                    <p class="font-body-sm text-body-sm leading-relaxed text-on-surface-variant">{{ office.address }}</p>
                  </div>
                </div>
              </div>
              <p v-else class="font-body-sm text-body-sm text-on-surface-variant">No offices added yet.</p>
            </template>

            <template v-else>
              <div v-for="(office, idx) in editOffices" :key="office.id || idx" class="mb-3 rounded-lg border border-outline-variant bg-surface-container-low p-4">
                <div class="grid gap-3 md:grid-cols-2">
                  <input v-model="office.name" class="rounded-lg border border-outline-variant bg-surface px-3 py-2 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary" placeholder="Office name" type="text" />
                  <input v-model="office.address" class="rounded-lg border border-outline-variant bg-surface px-3 py-2 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary" placeholder="Address *" type="text" />
                </div>
                <label class="mt-2 flex items-center gap-2 font-label-sm text-label-sm text-on-surface-variant">
                  <input v-model="office.is_headquarters" type="checkbox" class="h-4 w-4 rounded border-outline-variant" />
                  Headquarters
                </label>
                <div class="mt-3 flex gap-2">
                  <Button class="rounded-lg bg-primary px-4 py-1.5 font-label-sm text-label-sm text-white hover:bg-primary/90" :disabled="officeSaving === (office.id?.toString() ?? 'edit')" @click="saveOffice(office)">
                    <Loader2 v-if="officeSaving === (office.id?.toString() ?? 'edit')" class="mr-1 h-3 w-3 animate-spin" />
                    {{ officeSaving === (office.id?.toString() ?? 'edit') ? 'Saving...' : 'Save' }}
                  </Button>
                  <Button v-if="office.id" variant="outline" class="gap-1 rounded-lg border-error/30 px-4 py-1.5 font-label-sm text-label-sm text-error hover:bg-error/10" @click="removeOffice(office.id)">
                    <Trash2 class="h-3 w-3" />
                    Delete
                  </Button>
                </div>
              </div>
              <p v-if="!editOffices.length" class="font-body-sm text-body-sm text-on-surface-variant">No offices yet. Click "Add Office" to add one.</p>
            </template>
          </div>
        </div>

        <!-- Right Column -->
        <div class="flex flex-col gap-6">
          <!-- Profile Strength -->
          <div class="rounded-xl bg-surface-container p-6 shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
            <h2 class="mb-4 font-headline-sm text-headline-sm text-on-surface">Profile Strength</h2>

            <div class="relative mx-auto mb-4 flex h-32 w-32 items-center justify-center">
              <svg class="h-full w-full -rotate-90 transform" viewBox="0 0 100 100">
                <circle cx="50" cy="50" fill="none" r="44" stroke="var(--color-surface-variant)" stroke-width="8"></circle>
                <circle
                  cx="50"
                  cy="50"
                  fill="none"
                  r="44"
                  stroke="var(--color-primary)"
                  stroke-dasharray="276.46"
                  :stroke-dashoffset="276.46 - (276.46 * profileCompleteness) / 100"
                  stroke-width="8"
                  stroke-linecap="round"
                ></circle>
              </svg>
              <span class="absolute font-headline-md text-headline-md text-primary">{{ profileCompleteness }}%</span>
            </div>

            <p class="text-center font-body-sm text-body-sm text-on-surface-variant">
              <template v-if="profileCompleteness < 50">Add more details to attract candidates.</template>
              <template v-else-if="profileCompleteness < 80">Good progress! A few more details and you're set.</template>
              <template v-else>Your profile is looking great!</template>
            </p>
          </div>

          <!-- Save/Cancel buttons when editing -->
          <div v-if="isEditing" class="flex gap-3">
            <Button
              class="flex-1 rounded-lg bg-primary py-3 font-label-md text-label-md text-white hover:bg-primary/90"
              :disabled="isSaving"
              @click="handleSave"
            >
              <Loader2 v-if="isSaving" class="mr-2 h-4 w-4 animate-spin" />
              {{ isSaving ? 'Saving...' : 'Save Changes' }}
            </Button>
            <Button
              variant="outline"
              class="rounded-lg border-outline-variant px-6 py-3 font-label-md text-label-md text-on-surface hover:bg-surface-container-high"
              :disabled="isSaving"
              @click="cancelEdit"
            >
              Cancel
            </Button>
          </div>
        </div>
      </div>
    </div>
  </main>
</template>
