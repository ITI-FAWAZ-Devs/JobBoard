<script setup lang="ts">
import { BriefcaseBusiness, Building2, Check, Hexagon, UserRound } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { Button } from '@/components/ui/button';

type RegistrationRole = 'candidate' | 'employer';

const router = useRouter();
const selectedRole = ref<RegistrationRole | null>(null);
const canContinue = computed(() => selectedRole.value !== null);

function selectRole(role: RegistrationRole) {
  selectedRole.value = role;
}

function continueToRegister() {
  if (!selectedRole.value) return;
  router.push({ path: '/sign-up', query: { role: selectedRole.value } });
}
</script>

<template>
  <main
    class="relative flex min-h-screen w-full flex-col items-center justify-center overflow-hidden bg-background px-gutter py-xl text-on-background antialiased selection:bg-primary-container selection:text-on-primary-container"
  >
    <div class="pointer-events-none absolute left-[-10%] top-[-10%] h-[40%] w-[40%] rounded-full bg-primary-fixed-dim/20 blur-[100px]"></div>
    <div class="pointer-events-none absolute bottom-[-10%] right-[-10%] h-[30%] w-[30%] rounded-full bg-secondary-container/20 blur-[80px]"></div>

    <section class="z-10 mb-lg w-full max-w-container-max text-center">
      <RouterLink class="mb-md inline-flex cursor-pointer items-center justify-center gap-xs" to="/">
        <Hexagon class="h-9 w-9 fill-primary/10 text-primary" aria-hidden="true" />
        <span class="font-headline-md text-headline-md text-primary tracking-tight">WorkHive</span>
      </RouterLink>

      <h1 class="mb-sm hidden font-headline-lg text-headline-lg text-on-background md:block">
        Join as a Candidate or Employer?
      </h1>
      <h1 class="mb-sm block font-headline-lg-mobile text-headline-lg-mobile text-on-background md:hidden">
        Join as a Candidate or Employer?
      </h1>
      <p class="mx-auto font-body-md text-body-md text-on-surface-variant">
        Select your primary purpose to help us customize your onboarding experience.
      </p>
    </section>

    <section class="z-10 flex w-full max-w-4xl flex-col gap-md md:flex-row">
      <label
        class="group relative flex flex-1 cursor-pointer flex-col items-center rounded-xl border-2 p-md text-center transition-all duration-300"
        :class="
          selectedRole === 'candidate'
            ? 'border-primary bg-surface-container-low shadow-[0px_0px_0px_2px_#004ac6,0px_8px_16px_rgba(0,0,0,0.06)]'
            : 'border-transparent bg-surface-container-lowest shadow-[0px_4px_12px_rgba(0,0,0,0.05)] hover:shadow-[0px_12px_24px_rgba(0,0,0,0.08)]'
        "
        for="role-candidate"
      >
        <input
          id="role-candidate"
          class="sr-only"
          name="role"
          type="radio"
          value="candidate"
          :checked="selectedRole === 'candidate'"
          @change="selectRole('candidate')"
        />
        <span
          class="absolute right-sm top-sm flex h-6 w-6 items-center justify-center rounded-full border-2 transition-colors"
          :class="selectedRole === 'candidate' ? 'border-primary bg-primary' : 'border-outline-variant'"
        >
          <Check v-if="selectedRole === 'candidate'" class="h-4 w-4 text-on-primary" aria-hidden="true" />
        </span>

        <span class="mb-md flex h-20 w-20 items-center justify-center rounded-full bg-primary-container/10 text-primary transition-transform duration-300 group-hover:scale-105 group-hover:bg-primary-container/20">
          <span class="relative">
            <UserRound class="h-12 w-12" aria-hidden="true" />
            <span class="absolute bottom-[-4px] right-[-4px] rounded-full bg-surface-container-lowest p-1">
              <BriefcaseBusiness class="h-5 w-5 fill-primary/10 text-primary" aria-hidden="true" />
            </span>
          </span>
        </span>

        <h2 class="mb-xs font-headline-sm text-headline-sm text-on-surface">I'm a Candidate</h2>
        <p class="font-body-sm text-body-sm text-on-surface-variant">
          I'm looking for the next great opportunity to advance my career, discover jobs, and connect with top companies.
        </p>
      </label>

      <label
        class="group relative flex flex-1 cursor-pointer flex-col items-center rounded-xl border-2 p-md text-center transition-all duration-300"
        :class="
          selectedRole === 'employer'
            ? 'border-primary bg-surface-container-low shadow-[0px_0px_0px_2px_#004ac6,0px_8px_16px_rgba(0,0,0,0.06)]'
            : 'border-transparent bg-surface-container-lowest shadow-[0px_4px_12px_rgba(0,0,0,0.05)] hover:shadow-[0px_12px_24px_rgba(0,0,0,0.08)]'
        "
        for="role-employer"
      >
        <input
          id="role-employer"
          class="sr-only"
          name="role"
          type="radio"
          value="employer"
          :checked="selectedRole === 'employer'"
          @change="selectRole('employer')"
        />
        <span
          class="absolute right-sm top-sm flex h-6 w-6 items-center justify-center rounded-full border-2 transition-colors"
          :class="selectedRole === 'employer' ? 'border-primary bg-primary' : 'border-outline-variant'"
        >
          <Check v-if="selectedRole === 'employer'" class="h-4 w-4 text-on-primary" aria-hidden="true" />
        </span>

        <span class="mb-md flex h-20 w-20 items-center justify-center rounded-full bg-secondary-container/20 text-secondary transition-transform duration-300 group-hover:scale-105 group-hover:bg-secondary-container/40">
          <Building2 class="h-12 w-12" aria-hidden="true" />
        </span>

        <h2 class="mb-xs font-headline-sm text-headline-sm text-on-surface">I'm an Employer</h2>
        <p class="font-body-sm text-body-sm text-on-surface-variant">
          I'm looking to hire top talent, post open roles, and manage candidates efficiently for my organization.
        </p>
      </label>
    </section>

    <section class="z-10 mt-xl flex flex-col items-center">
      <Button
        class="mb-sm cursor-pointer rounded-full bg-primary px-xl py-sm font-label-md text-label-md text-on-primary shadow-sm transition-all duration-300 hover:bg-on-primary-fixed-variant disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none"
        :disabled="!canContinue"
        type="button"
        @click="continueToRegister"
      >
        Create Account
      </Button>
      <p class="font-body-sm text-body-sm text-on-surface-variant">
        Already have an account?
        <RouterLink class="cursor-pointer font-label-md text-label-md text-primary hover:underline" to="/sign-in">
          Sign In
        </RouterLink>
      </p>
    </section>
  </main>
</template>
