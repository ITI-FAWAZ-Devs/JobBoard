<script setup lang="ts">
import type { RegisterPayload } from '@/api/auth';
import { useRegister } from '@/Hooks/useRegister';
import { Button } from '@/components/ui/button';
import { BriefcaseBusiness } from 'lucide-vue-next';
import { computed, reactive } from 'vue';
import { toast } from 'vue-sonner';
import { RouterLink, useRoute, useRouter } from 'vue-router';

type RegistrationRole = 'candidate' | 'employer';

const route = useRoute();
const router = useRouter();

const activeRole = computed<RegistrationRole>(() =>
  route.query.role === 'employer' ? 'employer' : 'candidate',
);
const { mutate, isPending } = useRegister();

const candidateForm = reactive({
  name: '',
  email: '',
  password: '',
  passwordConfirmation: '',
  termsAccepted: false,
});

const employerForm = reactive({
  companyName: '',
  name: '',
  email: '',
  password: '',
  passwordConfirmation: '',
  termsAccepted: false,
});

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

  return firstValidationError || response?.message || 'Failed to create account. Please check your details.';
}

function buildPayload(): RegisterPayload {
  if (activeRole.value === 'employer') {
    return {
      name: employerForm.name,
      email: employerForm.email,
      password: employerForm.password,
      password_confirmation: employerForm.passwordConfirmation,
      role: 'employer',
      company_name: employerForm.companyName,
    };
  }

  return {
    name: candidateForm.name,
    email: candidateForm.email,
    password: candidateForm.password,
    password_confirmation: candidateForm.passwordConfirmation,
    role: 'candidate',
  };
}

function handleRegister() {
  mutate(buildPayload(), {
    onSuccess: () => {
      toast.success('Account created successfully.');
      const redirectTarget = typeof route.query.redirect === 'string'
        ? route.query.redirect
        : '/';
      router.push(redirectTarget);
    },
    onError: (error) => {
      toast.error(getErrorMessage(error));
    },
  });
}
</script>

<template>
  <main
    class="relative flex min-h-screen items-center justify-center overflow-hidden bg-background p-sm text-on-surface selection:bg-primary-container selection:text-on-primary md:p-gutter"
  >
    <div class="absolute inset-0 z-0">
      <div class="absolute inset-0 bg-linear-to-br from-surface-container-low to-surface-container opacity-80 mix-blend-multiply"></div>
      <img
        alt="Bright modern corporate office"
        class="h-full w-full object-cover opacity-20 grayscale"
        src="https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&q=80&w=1600"
      />
    </div>

    <section
      class="z-10 w-full max-w-140 rounded-xl border border-white/50 bg-white/95 p-md shadow-[0px_20px_40px_rgba(0,0,0,0.1)] backdrop-blur-md md:p-lg"
    >
      <div class="mb-lg text-center">
        <RouterLink
          class="mb-xs hidden cursor-pointer font-headline-lg text-headline-lg text-primary md:block"
          to="/"
        >
          WorkHive
        </RouterLink>
        <RouterLink
          class="mb-xs block cursor-pointer font-headline-lg-mobile text-headline-lg-mobile text-primary md:hidden"
          to="/"
        >
          WorkHive
        </RouterLink>
        <h1 class="mb-base font-headline-sm text-headline-sm text-on-surface">Create an Account</h1>
        <p class="font-body-sm text-body-sm text-on-surface-variant">Join the modern job marketplace.</p>
      </div>

      <div v-if="activeRole === 'candidate'" id="panel-candidate" aria-labelledby="tab-candidate" role="tabpanel">
        <Button
          class="mb-md flex w-full cursor-pointer items-center justify-center gap-xs rounded border border-outline-variant bg-transparent px-md py-sm text-on-surface transition-colors hover:bg-surface-container-lowest"
          type="button"
        >
          <BriefcaseBusiness class="h-5 w-5 text-primary" aria-hidden="true" />
          <span class="font-label-md text-label-md">Import from LinkedIn</span>
        </Button>

        <form class="space-y-sm" @submit.prevent="handleRegister">
          <div>
            <label class="mb-base block font-label-sm text-label-sm text-on-surface" for="c-fullname">Full Name</label>
            <input
              id="c-fullname"
              v-model="candidateForm.name"
              class="w-full rounded border border-outline-variant bg-surface-container-lowest px-sm py-xs font-body-md text-body-md text-on-surface placeholder:text-outline focus:border-primary focus:ring-1 focus:ring-primary"
              placeholder="John Doe"
              required
              type="text"
            />
          </div>

          <div>
            <label class="mb-base block font-label-sm text-label-sm text-on-surface" for="c-email">Email Address</label>
            <input
              id="c-email"
              v-model="candidateForm.email"
              class="w-full rounded border border-outline-variant bg-surface-container-lowest px-sm py-xs font-body-md text-body-md text-on-surface placeholder:text-outline focus:border-primary focus:ring-1 focus:ring-primary"
              placeholder="john@example.com"
              required
              type="email"
            />
          </div>

          <div class="grid grid-cols-1 gap-sm md:grid-cols-2">
            <div>
              <label class="mb-base block font-label-sm text-label-sm text-on-surface" for="c-password">Password</label>
              <input
                id="c-password"
                v-model="candidateForm.password"
                class="w-full rounded border border-outline-variant bg-surface-container-lowest px-sm py-xs font-body-md text-body-md text-on-surface placeholder:text-outline focus:border-primary focus:ring-1 focus:ring-primary"
                placeholder="••••••••"
                required
                type="password"
              />
            </div>
            <div>
              <label class="mb-base block font-label-sm text-label-sm text-on-surface" for="c-confirm-password">Confirm Password</label>
              <input
                id="c-confirm-password"
                v-model="candidateForm.passwordConfirmation"
                class="w-full rounded border border-outline-variant bg-surface-container-lowest px-sm py-xs font-body-md text-body-md text-on-surface placeholder:text-outline focus:border-primary focus:ring-1 focus:ring-primary"
                placeholder="••••••••"
                required
                type="password"
              />
            </div>
          </div>

          <div class="mt-md flex items-start gap-xs">
            <div class="flex h-5 items-center">
              <input
                id="c-terms"
                v-model="candidateForm.termsAccepted"
                class="h-4 w-4 rounded border-outline-variant bg-surface-container-lowest text-primary focus:ring-2 focus:ring-primary"
                required
                type="checkbox"
              />
            </div>
            <label class="font-body-sm text-body-sm text-on-surface-variant" for="c-terms">
              I agree to the
              <RouterLink class="cursor-pointer text-primary hover:underline" to="/terms-of-service">Terms of Service</RouterLink>
              and
              <RouterLink class="cursor-pointer text-primary hover:underline" to="/privacy-policy">Privacy Policy</RouterLink>.
            </label>
          </div>

          <Button
            class="mt-lg w-full cursor-pointer rounded bg-primary-container py-sm font-label-md text-label-md text-on-primary transition-colors hover:bg-primary active:scale-95"
            :disabled="isPending"
            type="submit"
          >
            {{ isPending ? 'Creating account...' : 'Create Account' }}
          </Button>
        </form>
      </div>

      <div v-else id="panel-employer" aria-labelledby="tab-employer" role="tabpanel">
        <form class="space-y-sm" @submit.prevent="handleRegister">
          <div>
            <label class="mb-base block font-label-sm text-label-sm text-on-surface" for="e-company">Company Name</label>
            <input
              id="e-company"
              v-model="employerForm.companyName"
              class="w-full rounded border border-outline-variant bg-surface-container-lowest px-sm py-xs font-body-md text-body-md text-on-surface placeholder:text-outline focus:border-primary focus:ring-1 focus:ring-primary"
              placeholder="Acme Corp"
              required
              type="text"
            />
          </div>

          <div>
            <label class="mb-base block font-label-sm text-label-sm text-on-surface" for="e-fullname">Full Name</label>
            <input
              id="e-fullname"
              v-model="employerForm.name"
              class="w-full rounded border border-outline-variant bg-surface-container-lowest px-sm py-xs font-body-md text-body-md text-on-surface placeholder:text-outline focus:border-primary focus:ring-1 focus:ring-primary"
              placeholder="Jane Smith"
              required
              type="text"
            />
          </div>

          <div>
            <label class="mb-base block font-label-sm text-label-sm text-on-surface" for="e-email">Work Email</label>
            <input
              id="e-email"
              v-model="employerForm.email"
              class="w-full rounded border border-outline-variant bg-surface-container-lowest px-sm py-xs font-body-md text-body-md text-on-surface placeholder:text-outline focus:border-primary focus:ring-1 focus:ring-primary"
              placeholder="jane@acmecorp.com"
              required
              type="email"
            />
          </div>

          <div class="grid grid-cols-1 gap-sm md:grid-cols-2">
            <div>
              <label class="mb-base block font-label-sm text-label-sm text-on-surface" for="e-password">Password</label>
              <input
                id="e-password"
                v-model="employerForm.password"
                class="w-full rounded border border-outline-variant bg-surface-container-lowest px-sm py-xs font-body-md text-body-md text-on-surface placeholder:text-outline focus:border-primary focus:ring-1 focus:ring-primary"
                placeholder="••••••••"
                required
                type="password"
              />
            </div>
            <div>
              <label class="mb-base block font-label-sm text-label-sm text-on-surface" for="e-confirm-password">Confirm Password</label>
              <input
                id="e-confirm-password"
                v-model="employerForm.passwordConfirmation"
                class="w-full rounded border border-outline-variant bg-surface-container-lowest px-sm py-xs font-body-md text-body-md text-on-surface placeholder:text-outline focus:border-primary focus:ring-1 focus:ring-primary"
                placeholder="••••••••"
                required
                type="password"
              />
            </div>
          </div>

          <div class="mt-md flex items-start gap-xs">
            <div class="flex h-5 items-center">
              <input
                id="e-terms"
                v-model="employerForm.termsAccepted"
                class="h-4 w-4 rounded border-outline-variant bg-surface-container-lowest text-primary focus:ring-2 focus:ring-primary"
                required
                type="checkbox"
              />
            </div>
            <label class="font-body-sm text-body-sm text-on-surface-variant" for="e-terms">
              I agree to the
              <RouterLink class="cursor-pointer text-primary hover:underline" to="/terms-of-service">Terms of Service</RouterLink>
              and
              <RouterLink class="cursor-pointer text-primary hover:underline" to="/privacy-policy">Privacy Policy</RouterLink>.
            </label>
          </div>

          <Button
            class="mt-lg w-full cursor-pointer rounded bg-primary-container py-sm font-label-md text-label-md text-on-primary transition-colors hover:bg-primary active:scale-95"
            :disabled="isPending"
            type="submit"
          >
            {{ isPending ? 'Creating account...' : 'Create Employer Account' }}
          </Button>
        </form>
      </div>

      <div class="mt-lg text-center">
        <p class="font-body-sm text-body-sm text-on-surface-variant">
          Already have an account?
          <RouterLink class="cursor-pointer font-medium text-primary hover:underline" to="/sign-in">Log in</RouterLink>
        </p>
      </div>
    </section>
  </main>
</template>
