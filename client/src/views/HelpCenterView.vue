<script setup lang="ts">
import { ref } from 'vue';
import { ChevronDown, BookOpen, Briefcase, User, MessageCircle, Mail } from 'lucide-vue-next';

const faqs = ref([
  {
    category: 'Getting Started',
    icon: BookOpen,
    items: [
      { q: 'How do I create an account?', a: 'Click "Sign Up" on the login page, choose your role (Employer or Candidate), and fill in your details. You\'ll receive a confirmation email to verify your account.' },
      { q: 'Can I switch my role later?', a: 'Each account is tied to one role. If you need to switch, please contact support to discuss your options.' },
      { q: 'How do I reset my password?', a: 'On the login page, click "Forgot Password" and enter your email. We\'ll send you a reset link that expires in 60 minutes.' },
    ],
  },
  {
    category: 'For Employers',
    icon: Briefcase,
    items: [
      { q: 'How do I post a job?', a: 'Navigate to your Dashboard and click "Post a Job". Fill in the title, description, requirements, location, salary range, and deadline. Your listing will be reviewed by admins before going live.' },
      { q: 'How do I review applications?', a: 'Go to your job listing and click on it to view all applicants. You can review each candidate\'s profile and contact them directly.' },
      { q: 'How do I unlock candidate contact details?', a: 'Select a job and candidate from the Checkout page, complete the payment via Stripe, and the candidate\'s email and phone will be revealed.' },
      { q: 'What payment methods are accepted?', a: 'We currently support Stripe (credit/debit cards). PayPal support is coming soon.' },
    ],
  },
  {
    category: 'For Candidates',
    icon: User,
    items: [
      { q: 'How do I apply for a job?', a: 'Browse job listings from the Dashboard, click on a job that interests you, and hit the "Apply" button. Your profile and resume will be shared with the employer.' },
      { q: 'How do I update my profile?', a: 'Go to your Profile page from the sidebar. You can add your experience, education, skills, and upload a resume to make your profile stand out.' },
      { q: 'How do I manage my applications?', a: 'Visit the Applications section to track the status of each application you\'ve submitted.' },
    ],
  },
]);

const openFaq = ref<Record<string, boolean>>({});

function toggleFaq(key: string) {
  openFaq.value[key] = !openFaq.value[key];
}
</script>

<template>
  <div class="min-h-screen bg-background text-on-background">
    <main class="mx-auto w-full max-w-container-max flex-1 p-md md:p-lg">
      <div class="mb-xl">
        <h1 class="font-headline-lg text-headline-lg text-on-background">Help Center</h1>
        <p class="mt-1 font-body-md text-body-md text-on-surface-variant">
          Find answers to common questions and learn how to make the most of WorkHive.
        </p>
      </div>

      <div class="grid gap-lg">
        <section v-for="section in faqs" :key="section.category" class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
          <div class="flex items-center gap-sm border-b border-outline-variant p-md">
            <component :is="section.icon" class="h-5 w-5 text-primary" aria-hidden="true" />
            <h2 class="font-headline-md text-headline-md text-on-background">{{ section.category }}</h2>
          </div>

          <div class="divide-y divide-outline-variant">
            <div v-for="(item, idx) in section.items" :key="idx" class="p-md">
              <button
                class="flex w-full items-center justify-between text-left font-label-md text-label-md text-on-background hover:text-primary"
                @click="toggleFaq(`${section.category}-${idx}`)"
              >
                {{ item.q }}
                <ChevronDown
                  class="h-4 w-4 shrink-0 text-on-surface-variant transition-transform duration-200"
                  :class="openFaq[`${section.category}-${idx}`] ? 'rotate-180' : ''"
                  aria-hidden="true"
                />
              </button>
              <div
                v-if="openFaq[`${section.category}-${idx}`]"
                class="mt-sm font-body-sm text-body-sm text-on-surface-variant leading-relaxed"
              >
                {{ item.a }}
              </div>
            </div>
          </div>
        </section>

        <section class="overflow-hidden rounded-xl bg-primary-container/30 shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
          <div class="flex flex-col items-center gap-md p-lg">
            <div class="flex items-center gap-sm">
              <MessageCircle class="h-5 w-5 text-primary" aria-hidden="true" />
              <h2 class="font-headline-md text-headline-md text-on-background">Still Need Help?</h2>
            </div>
            <p class="font-body-md text-body-md text-on-surface-variant max-w-lg text-left">
              Didn't find the answer you need? Contact our support team for personalized assistance.
            </p>
            <a
              href="mailto:support@workhive.com"
              class="inline-flex items-center gap-2 rounded-lg bg-primary px-md py-sm font-label-md text-label-md text-white hover:opacity-90"
            >
              <Mail class="h-4 w-4" aria-hidden="true" />
              Contact Support
            </a>
          </div>
        </section>
      </div>
    </main>
  </div>
</template>
