<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { toast } from 'vue-sonner';
import {
  BriefcaseBusiness,
  Edit3,
  FileText,
  Link2,
  Loader2,
  Mail,
  MapPin,
  Phone,
  Plus,
  School,
  Trash2,
  Upload,
  X,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { useProfile } from '@/Hooks/useProfile';
import { updateProfileApi } from '@/api/profile';
import { getOAuthConnectUrl } from '@/api/auth';
import {
  createEducationApi,
  createExperienceApi,
  deleteEducationApi,
  deleteExperienceApi,
  updateEducationApi,
  updateExperienceApi,
} from '@/api/candidate';
import type { UserProfile } from '@/types/profile';

type ExperienceItem = {
  title: string;
  company: string;
  location: string;
  period: string;
  description: string;
  current?: boolean;
};

type EducationItem = {
  title: string;
  school: string;
  period: string;
};

const { data: profile, refetch: profileRefetch } = useProfile();

const user = computed<UserProfile>(() => (profile.value ?? {}) as UserProfile);
const displayName = computed(() => user.value.name?.trim() || 'Candidate');
const avatarUrl = computed(() => user.value.avatar_url || '');
const avatarInitial = computed(() => displayName.value.charAt(0).toUpperCase());
const headline = computed(() => user.value.profile?.bio?.trim() || 'Open to opportunities');
const location = computed(() => user.value.profile?.location?.trim() || 'Remote');
const email = computed(() => user.value.email || 'your.email@example.com');
const phone = computed(() => user.value.profile?.phone?.trim() || '+1 (555) 123-4567');
const linkedinUrl = computed(() => user.value.profile?.linkedin_url?.trim() || 'Add LinkedIn in settings');
const skills = computed(() => user.value.profile?.skills?.filter(Boolean) ?? []);
const experienceYears = computed(() => user.value.profile?.experience_years ?? 8);
const resumeUrl = computed(() => user.value.profile?.resume_url || '');

const isSaving = ref(false);
const isEditing = ref(false);

const form = reactive({
  phone: '',
  linkedinUrl: '',
  location: '',
  bio: '',
  skills: '',
});

watch(
  user,
  (currentUser) => {
    form.phone = currentUser.profile?.phone || '';
    form.linkedinUrl = currentUser.profile?.linkedin_url || '';
    form.location = currentUser.profile?.location || '';
    form.bio = currentUser.profile?.bio || '';
    form.skills = currentUser.profile?.skills?.join(', ') || '';
  },
  { immediate: true },
);

function cancelEdit() {
  const u = user.value;
  form.phone = u.profile?.phone || '';
  form.linkedinUrl = u.profile?.linkedin_url || '';
  form.location = u.profile?.location || '';
  form.bio = u.profile?.bio || '';
  form.skills = u.profile?.skills?.join(', ') || '';
  editExpItems.value = JSON.parse(JSON.stringify(experiences.value));
  editEduItems.value = JSON.parse(JSON.stringify(education.value));
  newExpShow.value = false;
  newEduShow.value = false;
  resetNewExp();
  resetNewEdu();
  avatarFile.value = null;
  avatarPreview.value = '';
  isEditing.value = false;
}

function getErrorMessage(error: unknown) {
  const response = (error as {
    response?: { data?: { message?: string; errors?: Record<string, string[]> } };
  })?.response?.data;
  const firstValidationError = response?.errors ? Object.values(response.errors)[0]?.[0] : undefined;
  return firstValidationError || response?.message || 'Failed to update your profile.';
}

async function handleSave() {
  const payload = new FormData();
  payload.append('phone', form.phone);
  payload.append('linkedin_url', form.linkedinUrl);
  payload.append('location', form.location);
  payload.append('bio', form.bio);

  if (avatarFile.value) {
    payload.append('avatar', avatarFile.value);
  }

  if (resumeFile.value) {
    payload.append('resume', resumeFile.value);
  }

  const skillsArr = form.skills
    .split(',')
    .map((s) => s.trim())
    .filter(Boolean);
  skillsArr.forEach((skill) => payload.append('skills[]', skill));

  isSaving.value = true;
  try {
    await updateProfileApi(payload);
    avatarFile.value = null;
    avatarPreview.value = '';
    resumeFile.value = null;
    await profileRefetch();
    isEditing.value = false;
    toast.success('Profile updated successfully.');
  } catch (error) {
    toast.error(getErrorMessage(error));
  } finally {
    isSaving.value = false;
  }
}

// Experience data & CRUD
const experiences = computed(() => (user.value as any)?.experiences ?? []);
const editExpItems = ref<any[]>([]);
const newExpShow = ref(false);

function resetNewExp() {
  newExpItem.title = '';
  newExpItem.company = '';
  newExpItem.location = '';
  newExpItem.period = '';
  newExpItem.description = '';
  newExpItem.current = false;
}

const newExpItem = reactive({ title: '', company: '', location: '', period: '', description: '', current: false });
const expSaving = ref<string>('');

function syncExpData() {
  editExpItems.value = JSON.parse(JSON.stringify(experiences.value));
}

watch(experiences, syncExpData, { immediate: true });

async function addExperience() {
  if (!newExpItem.title || !newExpItem.company) return;
  expSaving.value = 'new';
  try {
    await createExperienceApi({ ...newExpItem });
    await profileRefetch();
    resetNewExp();
    newExpShow.value = false;
    toast.success('Experience added.');
  } catch (e) {
    toast.error(getErrorMessage(e));
  } finally {
    expSaving.value = '';
  }
}

async function saveExperience(item: any) {
  if (!item.title || !item.company) return;
  expSaving.value = item.id?.toString() ?? 'edit';
  try {
    if (item.id) {
      await updateExperienceApi(item.id, item);
    }
    await profileRefetch();
    toast.success('Experience saved.');
  } catch (e) {
    toast.error(getErrorMessage(e));
  } finally {
    expSaving.value = '';
  }
}

async function removeExperience(id: number) {
  if (!confirm('Delete this experience?')) return;
  try {
    await deleteExperienceApi(id);
    await profileRefetch();
    toast.success('Experience deleted.');
  } catch (e) {
    toast.error(getErrorMessage(e));
  }
}

// Education data & CRUD
const education = computed(() => (user.value as any)?.education ?? []);
const editEduItems = ref<any[]>([]);
const newEduShow = ref(false);

function resetNewEdu() {
  newEduItem.title = '';
  newEduItem.school = '';
  newEduItem.period = '';
}

const newEduItem = reactive({ title: '', school: '', period: '' });
const eduSaving = ref<string>('');

function syncEduData() {
  editEduItems.value = JSON.parse(JSON.stringify(education.value));
}

watch(education, syncEduData, { immediate: true });

async function addEducation() {
  if (!newEduItem.title || !newEduItem.school) return;
  eduSaving.value = 'new';
  try {
    await createEducationApi({ ...newEduItem });
    await profileRefetch();
    resetNewEdu();
    newEduShow.value = false;
    toast.success('Education added.');
  } catch (e) {
    toast.error(getErrorMessage(e));
  } finally {
    eduSaving.value = '';
  }
}

async function saveEducation(item: any) {
  if (!item.title || !item.school) return;
  eduSaving.value = item.id?.toString() ?? 'edit';
  try {
    if (item.id) {
      await updateEducationApi(item.id, item);
    }
    await profileRefetch();
    toast.success('Education saved.');
  } catch (e) {
    toast.error(getErrorMessage(e));
  } finally {
    eduSaving.value = '';
  }
}

async function removeEducation(id: number) {
  if (!confirm('Delete this education?')) return;
  try {
    await deleteEducationApi(id);
    await profileRefetch();
    toast.success('Education deleted.');
  } catch (e) {
    toast.error(getErrorMessage(e));
  }
}

// Avatar
const avatarFile = ref<File | null>(null);
const avatarPreview = ref('');

function handleAvatarChange(event: Event) {
  const input = event.target as HTMLInputElement;
  avatarFile.value = input.files?.[0] ?? null;
  if (avatarFile.value) {
    avatarPreview.value = URL.createObjectURL(avatarFile.value);
  }
}

// Resume
const resumeFile = ref<File | null>(null);
const resumeInputRef = ref<HTMLInputElement | null>(null);

function handleResumeChange(event: Event) {
  const input = event.target as HTMLInputElement;
  resumeFile.value = input.files?.[0] ?? null;
}

const profileCompleteness = computed(() => {
  const filledFields = [
    user.value.name,
    user.value.email,
    user.value.avatar_url,
    user.value.profile?.phone,
    user.value.profile?.linkedin_url,
    user.value.profile?.bio,
    (user.value.profile?.skills as any)?.length,
  ].filter(Boolean).length;

  return Math.min(100, Math.max(35, 35 + filledFields * 10));
});

const isLinkedInConnected = computed(() => Boolean(user.value.profile?.linkedin_url));

function handleConnectLinkedIn() {
  window.location.href = getOAuthConnectUrl('linkedin');
}

const route = useRoute();
onMounted(() => {
  const connectError = route.query.connect_error;
  if (typeof connectError === 'string' && connectError) {
    toast.error(connectError);
  }
});
</script>

<template>
  <main class="min-h-screen bg-surface p-4 text-on-surface md:p-6 lg:p-8">
    <div class="mx-auto max-w-7xl">
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <section class="flex flex-col gap-6 lg:col-span-1">
          <div class="relative overflow-hidden rounded-xl bg-surface-container p-6 text-center shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
            <div class="absolute left-0 top-0 h-24 w-full bg-primary/10"></div>

            <div class="relative mx-auto mt-2 h-32 w-32">
              <img v-if="avatarPreview || avatarUrl" :alt="`${displayName} avatar`" class="h-full w-full rounded-full border-4 border-surface object-cover shadow-sm" :src="avatarPreview || avatarUrl" />
              <span v-else class="flex h-full w-full items-center justify-center rounded-full border-4 border-surface bg-surface-container-lowest text-4xl font-bold text-primary shadow-sm">
                {{ avatarInitial }}
              </span>

              <svg class="pointer-events-none absolute inset-0 h-full w-full -rotate-90 transform" viewBox="0 0 100 100">
                <circle cx="50" cy="50" fill="none" r="48" stroke="#e5eeff" stroke-width="4"></circle>
                <circle
                  cx="50"
                  cy="50"
                  fill="none"
                  r="48"
                  stroke="#2563eb"
                  stroke-dasharray="301.59"
                  :stroke-dashoffset="301.59 - (301.59 * profileCompleteness) / 100"
                  stroke-width="4"
                ></circle>
              </svg>

              <div class="absolute -bottom-2 -right-2 rounded-full border-2 border-surface bg-primary px-2 py-1 text-xs font-semibold text-white shadow-sm">
                {{ profileCompleteness }}%
              </div>

              <label v-if="isEditing" class="absolute -left-2 -top-2 flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border-2 border-surface bg-primary text-white shadow-sm hover:bg-primary/90">
                <input class="hidden" accept="image/*" type="file" @change="handleAvatarChange" />
                <Edit3 class="h-4 w-4" />
              </label>
            </div>

            <h1 class="mt-4 font-headline-sm text-headline-sm text-on-surface">{{ displayName }}</h1>
            <!-- <p class="mt-1 font-body-md text-body-md text-on-surface-variant">{{ headline }}</p> -->

            <div class="mt-3 flex items-center justify-center gap-1 text-on-surface-variant">
              <MapPin class="h-4 w-4" aria-hidden="true" />
              <span class="font-body-sm text-body-sm">{{ location }}</span>
            </div>

            <div class="mt-4 flex flex-col gap-3">
              <Button
                v-if="!isLinkedInConnected"
                class="flex items-center justify-center gap-2 rounded-lg bg-[#0A66C2] px-4 py-2 font-label-md text-label-md text-white hover:bg-[#095aa8]"
                @click="handleConnectLinkedIn"
              >
                <BriefcaseBusiness class="h-4 w-4" aria-hidden="true" />
                Connect LinkedIn
              </Button>
              <Button
                v-else
                as-child
                variant="outline"
                class="flex items-center justify-center gap-2 rounded-lg border-[#0A66C2]/40 px-4 py-2 font-label-md text-label-md text-[#0A66C2] hover:bg-[#0A66C2]/5"
              >
                <a :href="user.profile?.linkedin_url ?? '#'" target="_blank" rel="noreferrer">
                  <BriefcaseBusiness class="h-4 w-4" aria-hidden="true" />
                  LinkedIn Connected
                </a>
              </Button>
              <Button variant="outline" class="rounded-lg border-outline-variant bg-surface-container px-4 py-2 font-label-md text-label-md text-on-surface hover:bg-surface-container-high" @click="isEditing = true">
                <Edit3 class="h-4 w-4" aria-hidden="true" />
                Edit Profile
              </Button>
            </div>
          </div>

          <div class="rounded-xl bg-surface-container p-6 shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
            <div class="mb-4 flex items-center justify-between">
              <h2 class="font-headline-sm text-headline-sm text-on-surface">Contact Info</h2>
              <Button v-if="!isEditing" variant="ghost" size="icon" class="text-on-surface-variant hover:text-primary" @click="isEditing = true">
                <Edit3 class="h-4 w-4" aria-hidden="true" />
              </Button>
            </div>

            <form class="space-y-4" @submit.prevent="handleSave">
              <div class="flex items-start gap-3">
                <div class="rounded-lg bg-surface-container-low p-2 text-primary">
                  <Mail class="h-5 w-5" aria-hidden="true" />
                </div>
                <div class="flex-1">
                  <p class="font-label-sm text-label-sm text-on-surface-variant">Email</p>
                  <p class="font-body-sm text-body-sm leading-relaxed text-on-surface">{{ email }}</p>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <div class="rounded-lg bg-surface-container-low p-2 text-primary">
                  <Phone class="h-5 w-5" aria-hidden="true" />
                </div>
                <div class="flex-1">
                  <p class="font-label-sm text-label-sm text-on-surface-variant">Phone</p>
                  <p v-if="!isEditing" class="font-body-sm text-body-sm text-on-surface">{{ phone }}</p>
                  <input
                    v-else
                    v-model="form.phone"
                    class="w-full rounded-lg border border-outline-variant bg-surface px-3 py-2 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary"
                    placeholder="+1 (555) 123-4567"
                    type="text"
                  />
                </div>
              </div>

              <div class="flex items-start gap-3">
                <div class="rounded-lg bg-surface-container-low p-2 text-primary">
                  <Link2 class="h-5 w-5" aria-hidden="true" />
                </div>
                <div class="flex-1">
                  <p class="font-label-sm text-label-sm text-on-surface-variant">LinkedIn</p>
                  <p v-if="!isEditing" class="break-all font-body-sm text-body-sm text-on-surface">{{ linkedinUrl }}</p>
                  <input
                    v-else
                    v-model="form.linkedinUrl"
                    class="w-full rounded-lg border border-outline-variant bg-surface px-3 py-2 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary"
                    placeholder="https://linkedin.com/in/yourprofile"
                    type="url"
                  />
                </div>
              </div>

              <div class="flex items-start gap-3">
                <div class="rounded-lg bg-surface-container-low p-2 text-primary">
                  <MapPin class="h-5 w-5" aria-hidden="true" />
                </div>
                <div class="flex-1">
                  <p class="font-label-sm text-label-sm text-on-surface-variant">Location</p>
                  <p v-if="!isEditing" class="font-body-sm text-body-sm text-on-surface">{{ location }}</p>
                  <input
                    v-else
                    v-model="form.location"
                    class="w-full rounded-lg border border-outline-variant bg-surface px-3 py-2 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary"
                    placeholder="City, Country"
                    type="text"
                  />
                </div>
              </div>

              <div v-if="isEditing" class="flex gap-2">
                <Button
                  class="flex-1 rounded-lg bg-primary py-2 font-label-md text-label-md text-white hover:bg-primary/90"
                  :disabled="isSaving"
                  type="submit"
                >
                  <Loader2 v-if="isSaving" class="mr-2 h-4 w-4 animate-spin" />
                  {{ isSaving ? 'Saving...' : 'Save' }}
                </Button>
                <Button
                  variant="outline"
                  class="rounded-lg border-outline-variant px-4 py-2 font-label-md text-label-md text-on-surface hover:bg-surface-container-high"
                  :disabled="isSaving"
                  type="button"
                  @click="cancelEdit"
                >
                  Cancel
                </Button>
              </div>
            </form>
          </div>
        </section>

        <section class="flex flex-col gap-6 lg:col-span-2">
          <div class="rounded-xl bg-surface-container p-6 shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
            <div class="mb-3 flex items-start justify-between">
              <h2 class="font-headline-sm text-headline-sm text-on-surface">About Me</h2>
              <Button v-if="!isEditing" variant="ghost" size="icon" class="text-on-surface-variant hover:text-primary" @click="isEditing = true">
                <Edit3 class="h-4 w-4" aria-hidden="true" />
              </Button>
            </div>

            <p v-if="!isEditing" class="font-body-md text-body-md leading-relaxed text-on-surface-variant">
              {{ headline }}
            </p>
            <textarea
              v-else
              v-model="form.bio"
              class="min-h-24 w-full rounded-lg border border-outline-variant bg-surface px-4 py-3 font-body-md text-body-md leading-relaxed text-on-surface outline-none focus:border-primary"
              placeholder="Tell employers about your background and goals."
            ></textarea>
          </div>

          <div class="rounded-xl bg-surface-container p-6 shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
            <div class="mb-4 flex items-center justify-between border-b border-outline-variant pb-2">
              <h2 class="font-headline-sm text-headline-sm text-on-surface">Experience</h2>
              <div class="flex items-center gap-1">
                <Button v-if="!isEditing" variant="ghost" size="icon" class="text-on-surface-variant hover:text-primary" @click="isEditing = true">
                  <Edit3 class="h-4 w-4" />
                </Button>
                <Button v-if="isEditing" variant="ghost" class="gap-1 font-label-sm text-label-sm text-primary hover:text-primary/80" @click="newExpShow = !newExpShow">
                <Plus class="h-4 w-4" aria-hidden="true" />
                {{ newExpShow ? 'Cancel' : 'Add Experience' }}
              </Button>
            </div>
            </div>

            <div class="flex flex-col gap-6">
              <div v-if="isEditing && newExpShow" class="rounded-lg border border-primary/30 bg-surface-container-low p-4">
                <div class="grid gap-3 md:grid-cols-2">
                  <input v-model="newExpItem.title" class="rounded-lg border border-outline-variant bg-surface px-3 py-2 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary" placeholder="Title *" type="text" />
                  <input v-model="newExpItem.company" class="rounded-lg border border-outline-variant bg-surface px-3 py-2 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary" placeholder="Company *" type="text" />
                  <input v-model="newExpItem.location" class="rounded-lg border border-outline-variant bg-surface px-3 py-2 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary" placeholder="Location" type="text" />
                  <input v-model="newExpItem.period" class="rounded-lg border border-outline-variant bg-surface px-3 py-2 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary" placeholder="e.g. Jan 2021 - Present" type="text" />
                </div>
                <textarea v-model="newExpItem.description" class="mt-3 min-h-20 w-full rounded-lg border border-outline-variant bg-surface px-3 py-2 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary" placeholder="Description"></textarea>
                <label class="mt-2 flex items-center gap-2 font-label-sm text-label-sm text-on-surface-variant">
                  <input v-model="newExpItem.current" type="checkbox" class="h-4 w-4 rounded border-outline-variant" />
                  Currently working here
                </label>
                <div class="mt-3 flex gap-2">
                  <Button class="rounded-lg bg-primary px-4 py-1.5 font-label-sm text-label-sm text-white hover:bg-primary/90" :disabled="expSaving === 'new'" @click="addExperience">
                    <Loader2 v-if="expSaving === 'new'" class="mr-1 h-3 w-3 animate-spin" />
                    {{ expSaving === 'new' ? 'Adding...' : 'Add' }}
                  </Button>
                  <Button variant="outline" class="rounded-lg border-outline-variant px-4 py-1.5 font-label-sm text-label-sm text-on-surface" @click="newExpShow = false; resetNewExp()">Cancel</Button>
                </div>
              </div>

              <template v-if="!isEditing">
                <article v-for="(item, idx) in experiences" :key="item.id || idx" class="group relative pl-6">
                  <div class="absolute left-0 top-1 flex h-6 w-6 items-center justify-center rounded-full border-2 border-surface bg-surface-container">
                    <div class="h-2 w-2 rounded-full" :class="item.current ? 'bg-primary' : 'bg-outline-variant'"></div>
                  </div>
                  <div class="ml-2">
                    <div class="flex items-start justify-between gap-4">
                      <div>
                        <h3 class="font-label-md text-label-md text-on-surface">{{ item.title }}</h3>
                        <p class="font-body-sm text-body-sm leading-relaxed text-on-surface-variant">{{ item.company }}{{ item.location ? ' • ' + item.location : '' }}</p>
                        <p class="font-label-sm text-label-sm text-outline">{{ item.period || '' }}</p>
                      </div>
                    </div>
                    <p v-if="item.description" class="mt-3 font-body-sm text-body-sm leading-relaxed text-on-surface-variant">{{ item.description }}</p>
                  </div>
                </article>
                <p v-if="!experiences.length" class="py-4 text-center font-body-sm text-body-sm text-on-surface-variant">No experience added yet.</p>
              </template>

              <template v-else>
                <div v-for="(item, idx) in editExpItems" :key="item.id || idx" class="rounded-lg border border-outline-variant bg-surface-container-low p-4">
                  <div class="grid gap-3 md:grid-cols-2">
                    <input v-model="item.title" class="rounded-lg border border-outline-variant bg-surface px-3 py-2 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary" placeholder="Title *" type="text" />
                    <input v-model="item.company" class="rounded-lg border border-outline-variant bg-surface px-3 py-2 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary" placeholder="Company *" type="text" />
                    <input v-model="item.location" class="rounded-lg border border-outline-variant bg-surface px-3 py-2 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary" placeholder="Location" type="text" />
                    <input v-model="item.period" class="rounded-lg border border-outline-variant bg-surface px-3 py-2 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary" placeholder="e.g. Jan 2021 - Present" type="text" />
                  </div>
                  <textarea v-model="item.description" class="mt-3 min-h-20 w-full rounded-lg border border-outline-variant bg-surface px-3 py-2 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary" placeholder="Description"></textarea>
                  <label class="mt-2 flex items-center gap-2 font-label-sm text-label-sm text-on-surface-variant">
                    <input v-model="item.current" type="checkbox" class="h-4 w-4 rounded border-outline-variant" />
                    Currently working here
                  </label>
                  <div class="mt-3 flex gap-2">
                    <Button class="rounded-lg bg-primary px-4 py-1.5 font-label-sm text-label-sm text-white hover:bg-primary/90" :disabled="expSaving === (item.id?.toString() ?? 'edit')" @click="saveExperience(item)">
                      <Loader2 v-if="expSaving === (item.id?.toString() ?? 'edit')" class="mr-1 h-3 w-3 animate-spin" />
                      {{ expSaving === (item.id?.toString() ?? 'edit') ? 'Saving...' : 'Save' }}
                    </Button>
                    <Button v-if="item.id" variant="outline" class="gap-1 rounded-lg border-error/30 px-4 py-1.5 font-label-sm text-label-sm text-error hover:bg-error/10" @click="removeExperience(item.id)">
                      <Trash2 class="h-3 w-3" />
                      Delete
                    </Button>
                  </div>
                </div>
                <p v-if="!editExpItems.length" class="py-4 text-center font-body-sm text-body-sm text-on-surface-variant">No experience yet. Click "Add Experience" to add one.</p>
              </template>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="rounded-xl bg-surface-container p-6 shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
              <div class="mb-4 flex items-center justify-between border-b border-outline-variant pb-2">
                <h2 class="font-headline-sm text-headline-sm text-on-surface">Education</h2>
              <div class="flex items-center gap-1">
                <Button v-if="!isEditing" variant="ghost" size="icon" class="text-on-surface-variant hover:text-primary" @click="isEditing = true">
                  <Edit3 class="h-4 w-4" />
                </Button>
                <Button v-if="isEditing" variant="ghost" class="gap-1 font-label-sm text-label-sm text-primary hover:text-primary/80" @click="newEduShow = !newEduShow">
                  <Plus class="h-4 w-4" aria-hidden="true" />
                  {{ newEduShow ? 'Cancel' : 'Add' }}
                </Button>
              </div>
              </div>

              <div class="space-y-4">
                <div v-if="isEditing && newEduShow" class="rounded-lg border border-primary/30 bg-surface-container-low p-4">
                  <div class="grid gap-3 md:grid-cols-2">
                    <input v-model="newEduItem.title" class="rounded-lg border border-outline-variant bg-surface px-3 py-2 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary" placeholder="Title *" type="text" />
                    <input v-model="newEduItem.school" class="rounded-lg border border-outline-variant bg-surface px-3 py-2 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary" placeholder="School *" type="text" />
                    <input v-model="newEduItem.period" class="rounded-lg border border-outline-variant bg-surface px-3 py-2 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary" placeholder="e.g. 2013 - 2017" type="text" />
                  </div>
                  <div class="mt-3 flex gap-2">
                    <Button class="rounded-lg bg-primary px-4 py-1.5 font-label-sm text-label-sm text-white hover:bg-primary/90" :disabled="eduSaving === 'new'" @click="addEducation">
                      <Loader2 v-if="eduSaving === 'new'" class="mr-1 h-3 w-3 animate-spin" />
                      {{ eduSaving === 'new' ? 'Adding...' : 'Add' }}
                    </Button>
                    <Button variant="outline" class="rounded-lg border-outline-variant px-4 py-1.5 font-label-sm text-label-sm text-on-surface" @click="newEduShow = false; resetNewEdu()">Cancel</Button>
                  </div>
                </div>

                <template v-if="!isEditing">
                  <article v-for="(item, idx) in education" :key="item.id || idx" class="flex gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded bg-surface-container-low text-primary">
                      <School class="h-5 w-5" aria-hidden="true" />
                    </div>
                    <div class="flex-1">
                      <div class="flex items-start justify-between gap-4">
                        <div>
                          <h3 class="font-label-md text-label-md text-on-surface">{{ item.title }}</h3>
                          <p class="font-body-sm text-body-sm leading-relaxed text-on-surface-variant">{{ item.school }}</p>
                          <p class="font-label-sm text-label-sm text-outline">{{ item.period || '' }}</p>
                        </div>
                      </div>
                    </div>
                  </article>
                  <p v-if="!education.length" class="py-4 text-center font-body-sm text-body-sm text-on-surface-variant">No education added yet.</p>
                </template>

                <template v-else>
                  <div v-for="(item, idx) in editEduItems" :key="item.id || idx" class="rounded-lg border border-outline-variant bg-surface-container-low p-4">
                    <div class="grid gap-3 md:grid-cols-2">
                      <input v-model="item.title" class="rounded-lg border border-outline-variant bg-surface px-3 py-2 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary" placeholder="Title *" type="text" />
                      <input v-model="item.school" class="rounded-lg border border-outline-variant bg-surface px-3 py-2 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary" placeholder="School *" type="text" />
                      <input v-model="item.period" class="rounded-lg border border-outline-variant bg-surface px-3 py-2 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary" placeholder="e.g. 2013 - 2017" type="text" />
                    </div>
                    <div class="mt-3 flex gap-2">
                      <Button class="rounded-lg bg-primary px-4 py-1.5 font-label-sm text-label-sm text-white hover:bg-primary/90" :disabled="eduSaving === (item.id?.toString() ?? 'edit')" @click="saveEducation(item)">
                        <Loader2 v-if="eduSaving === (item.id?.toString() ?? 'edit')" class="mr-1 h-3 w-3 animate-spin" />
                        {{ eduSaving === (item.id?.toString() ?? 'edit') ? 'Saving...' : 'Save' }}
                      </Button>
                      <Button v-if="item.id" variant="outline" class="gap-1 rounded-lg border-error/30 px-4 py-1.5 font-label-sm text-label-sm text-error hover:bg-error/10" @click="removeEducation(item.id)">
                        <Trash2 class="h-3 w-3" />
                        Delete
                      </Button>
                    </div>
                  </div>
                  <p v-if="!editEduItems.length" class="py-4 text-center font-body-sm text-body-sm text-on-surface-variant">No education yet. Click "Add" to add one.</p>
                </template>
              </div>
            </div>

            <div class="rounded-xl bg-surface-container p-6 shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
              <div class="mb-4 flex items-center justify-between border-b border-outline-variant pb-2">
                <h2 class="font-headline-sm text-headline-sm text-on-surface">Skills</h2>
                <Button v-if="!isEditing" variant="ghost" class="gap-1 font-label-sm text-label-sm text-primary hover:text-primary/80" @click="isEditing = true">
                  <Edit3 class="h-4 w-4" aria-hidden="true" />
                </Button>
              </div>

              <template v-if="!isEditing">
                <div class="flex flex-wrap gap-2">
                  <span
                    v-for="skill in skills.length ? skills : []"
                    :key="skill"
                    class="rounded-full border border-outline-variant bg-surface-container-low px-3 py-1 font-label-sm text-label-sm text-primary"
                  >
                    {{ skill }}
                  </span>
                </div>
              </template>

              <template v-else>
                <input
                  v-model="form.skills"
                  class="w-full rounded-lg border border-outline-variant bg-surface px-4 py-3 font-body-sm text-body-sm text-on-surface outline-none focus:border-primary"
                  placeholder="Vue, TypeScript, Tailwind"
                  type="text"
                />
                <p class="mt-2 font-label-sm text-label-sm text-outline">Comma-separated list of skills</p>

                <div class="mt-4 flex flex-wrap gap-2">
                  <span
                    v-for="skill in form.skills.split(',').map(s => s.trim()).filter(Boolean)"
                    :key="skill"
                    class="rounded-full border border-outline-variant bg-surface-container-low px-3 py-1 font-label-sm text-label-sm text-primary"
                  >
                    {{ skill }}
                  </span>
                </div>
              </template>
            </div>
          </div>

          <div class="rounded-xl bg-surface-container p-6 shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
            <div class="mb-4 flex items-center justify-between">
              <h2 class="font-headline-sm text-headline-sm text-on-surface">Resume / CV</h2>
            </div>

            <div class="flex flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-outline-variant bg-surface-container-low p-6 text-center transition-colors hover:bg-surface-container">
              <div class="mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-white text-primary shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
                <Upload class="h-5 w-5" aria-hidden="true" />
              </div>
              <p class="font-label-md text-label-md text-on-surface">{{ resumeUrl || resumeFile ? (resumeFile ? resumeFile.name : 'Resume uploaded') : 'Click to upload or drag and drop' }}</p>
              <p class="font-body-sm text-body-sm leading-relaxed text-on-surface-variant">PDF, DOCX up to 5MB</p>
              <input accept=".pdf,.docx,.doc" type="file" class="hidden" ref="resumeInputRef" @change="handleResumeChange" />
              <Button variant="outline" class="mt-2 rounded-lg border-outline-variant px-4 py-2 font-label-sm text-label-sm text-on-surface" @click="($refs.resumeInputRef as any)?.click()">
                {{ resumeFile ? 'Change File' : 'Browse File' }}
              </Button>
            </div>

            <div v-if="resumeFile" class="mt-3 flex items-center justify-between rounded-lg border border-primary/30 bg-primary/5 p-3">
              <div class="flex items-center gap-3">
                <FileText class="h-5 w-5 text-primary" />
                <div>
                  <p class="font-label-sm text-label-sm text-on-surface">{{ resumeFile.name }}</p>
                  <p class="font-body-sm text-body-sm text-on-surface-variant text-xs">{{ (resumeFile.size / 1024).toFixed(1) }} KB</p>
                </div>
              </div>
              <Button variant="ghost" size="icon" class="text-on-surface-variant hover:text-error" @click="resumeFile = null">
                <X class="h-4 w-4" />
              </Button>
            </div>

            <div v-if="resumeUrl && !resumeFile" class="mt-4 flex items-center justify-between rounded-lg border border-outline-variant bg-surface p-3">
              <div class="flex items-center gap-3">
                <div class="text-error">
                  <FileText class="h-5 w-5" aria-hidden="true" />
                </div>
                <div>
                  <p class="font-label-sm text-label-sm text-on-surface">Current Resume</p>
                  <p class="font-body-sm text-body-sm text-on-surface-variant text-xs">View or replace above</p>
                </div>
              </div>
              <Button as-child variant="ghost" size="icon" class="text-on-surface-variant hover:text-primary">
                <a :href="resumeUrl" target="_blank" rel="noreferrer">
                  <FileText class="h-5 w-5" aria-hidden="true" />
                </a>
              </Button>
            </div>
          </div>
        </section>
      </div>
    </div>
  </main>
</template>
