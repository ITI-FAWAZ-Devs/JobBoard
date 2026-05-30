<script setup lang="ts">
import { useLogin } from '@/Hooks/useLogin';
import { Button } from '@/components/ui/button';
import { ArrowRight, Check, Lock, Mail } from 'lucide-vue-next';
import { reactive } from 'vue';
import { toast } from 'vue-sonner';
import { RouterLink, useRoute, useRouter } from 'vue-router';

const router = useRouter();
const route = useRoute();
const { mutate, isPending } = useLogin();

const form = reactive({
  email: '',
  password: '',
  remember: false,
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

  return firstValidationError || response?.message || 'Failed to log in. Please check your credentials.';
}

function handleLogin() {
  mutate(
    {
      email: form.email,
      password: form.password,
    },
    {
      onSuccess: (res) => {
        toast.success('Logged in successfully.');
        const redirectTarget = typeof route.query.redirect === 'string'
          ? route.query.redirect
          : undefined;
        const role = res?.data?.user?.role;

        if (redirectTarget) {
          router.push(redirectTarget);
          return;
        }

        if (role === 'admin') {
          router.push('/admin');
          return;
        }

        if (role === 'employer') {
          router.push('/employer/analytics');
          return;
        }

        router.push('/');
      },
      onError: (error) => {
        toast.error(getErrorMessage(error));
      },
    },
  );
}
</script>

<template>
  <main
    class="flex min-h-screen items-center justify-center bg-gradient-mesh p-sm text-on-surface md:p-md"
    dir="ltr"
  >
    <section class="w-full max-w-120">
      <div class="mb-lg text-center">
        <RouterLink class="cursor-pointer font-headline-lg text-headline-lg tracking-tight text-primary" to="/">
          WorkHive
        </RouterLink>
      </div>

      <div class="rounded-xl bg-surface-container-lowest p-md shadow-[0px_20px_40px_rgba(0,0,0,0.1)] md:p-lg">
        <h1 class="mb-xs text-center font-headline-md text-headline-md">Welcome back</h1>
        <p class="mb-md text-center font-body-sm text-body-sm text-on-surface-variant">
          Enter your details to access your account.
        </p>

        <form class="space-y-md" @submit.prevent="handleLogin">
          <div>
            <label class="mb-xs block font-label-md text-label-md text-on-surface" for="email">
              Email Address
            </label>
            <div class="relative">
              <Mail class="absolute left-sm top-1/2 h-5 w-5 -translate-y-1/2 text-outline" aria-hidden="true" />
              <input
                id="email"
                v-model="form.email"
                class="w-full rounded border border-outline-variant bg-surface-container-lowest py-xs pl-11 pr-sm font-body-md text-body-md text-on-surface outline-none transition-all placeholder:text-outline-variant focus:border-primary focus:ring-1 focus:ring-primary"
                name="email"
                placeholder="name@example.com"
                required
                type="email"
              />
            </div>
          </div>

          <div>
            <label class="mb-xs block font-label-md text-label-md text-on-surface" for="password">
              Password
            </label>
            <div class="relative">
              <Lock class="absolute left-sm top-1/2 h-5 w-5 -translate-y-1/2 text-outline" aria-hidden="true" />
              <input
                id="password"
                v-model="form.password"
                class="w-full rounded border border-outline-variant bg-surface-container-lowest py-xs pl-11 pr-sm font-body-md text-body-md text-on-surface outline-none transition-all placeholder:text-outline-variant focus:border-primary focus:ring-1 focus:ring-primary"
                name="password"
                placeholder="••••••••"
                required
                type="password"
              />
            </div>
          </div>

          <div class="flex items-center justify-between">
            <label class="group flex cursor-pointer items-center gap-xs">
              <span class="relative flex h-4 w-4 items-center justify-center">
                <input
                  v-model="form.remember"
                  class="peer h-4 w-4 cursor-pointer appearance-none rounded-[2px] border border-outline-variant transition-colors checked:border-primary checked:bg-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                  type="checkbox"
                />
                <Check
                  class="pointer-events-none absolute h-3 w-3 text-on-primary opacity-0 peer-checked:opacity-100"
                  aria-hidden="true"
                />
              </span>
              <span class="font-body-sm text-body-sm text-on-surface-variant transition-colors group-hover:text-on-surface">
                Remember me
              </span>
            </label>

            <RouterLink
              class="cursor-pointer font-label-sm text-label-sm text-primary transition-colors hover:text-primary-fixed-dim"
              to="/forgot-password"
            >
              Forgot Password?
            </RouterLink>
          </div>

          <Button
            class="flex w-full cursor-pointer items-center justify-center gap-xs rounded bg-primary-container py-sm font-label-md text-label-md text-on-primary outline-none transition-colors hover:bg-primary focus:ring-4 focus:ring-primary/20"
            :disabled="isPending"
            type="submit"
          >
            {{ isPending ? 'Logging in...' : 'Login' }}
            <ArrowRight class="h-4.5 w-4.5" aria-hidden="true" />
          </Button>
        </form>

        <div class="relative mt-lg">
          <div aria-hidden="true" class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-outline-variant"></div>
          </div>
          <div class="relative flex justify-center">
            <span class="bg-surface-container-lowest px-sm font-body-sm text-body-sm text-on-surface-variant">
              Or continue with
            </span>
          </div>
        </div>

        <div class="mt-md grid grid-cols-2 gap-sm">
          <Button
            class="flex w-full cursor-pointer items-center justify-center gap-xs rounded border border-outline-variant bg-surface-container-lowest px-sm py-xs font-label-sm text-label-sm text-on-surface transition-colors hover:bg-surface-container-low"
            type="button"
          >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4" />
              <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
              <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05" />
              <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
            </svg>
            Google
          </Button>

          <Button
            class="flex w-full cursor-pointer items-center justify-center gap-xs rounded border border-outline-variant bg-surface-container-lowest px-sm py-xs font-label-sm text-label-sm text-on-surface transition-colors hover:bg-surface-container-low"
            type="button"
          >
            <svg class="h-5 w-5" fill="#0A66C2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
            </svg>
            LinkedIn
          </Button>
        </div>

        <p class="mt-md text-center font-body-sm text-body-sm text-on-surface-variant">
          Don't have an account?
          <RouterLink class="cursor-pointer font-label-sm text-label-sm text-primary hover:underline" to="/sign-up">
            Register
          </RouterLink>
        </p>
      </div>
    </section>
  </main>
</template>
