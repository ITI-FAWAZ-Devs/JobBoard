<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { Button } from "@/components/ui/button";
import { toast } from "vue-sonner";
import { CheckCircle2, Mail, Phone, ArrowLeft, RefreshCw } from "lucide-vue-next";
import Navbar from "@/components/shared/Navbar.vue";
import Footer from "@/components/shared/Footer.vue";
import { getCandidateContactApi, verifyPaymentStatusApi } from "@/api/employer";

const route = useRoute();
const router = useRouter();
const applicationId = Number(route.params.applicationId);
const sessionId = route.query.session_id as string | undefined;

const contact = ref<{ email: string; phone?: string | null } | null>(null);
const loading = ref(true);
const verifying = ref(true);
const showConfetti = ref(false);

async function verifyAndLoad() {
  verifying.value = true;
  let paid = false;

  for (let i = 0; i < 10; i++) {
    try {
      const res = await verifyPaymentStatusApi(applicationId);
      if (res.data.paid) {
        paid = true;
        break;
      }
    } catch {
      // retry
    }
    await new Promise((r) => setTimeout(r, 1000));
  }

  verifying.value = false;

  if (paid) {
    showConfetti.value = true;
    try {
      const res = await getCandidateContactApi(applicationId);
      contact.value = res.data;
    } catch {
      toast.error("Failed to load candidate contact details");
    } finally {
      loading.value = false;
    }

    setTimeout(() => {
      showConfetti.value = false;
    }, 4000);
  } else {
    loading.value = false;
    toast.error("Payment could not be verified. Please contact support.");
  }
}

onMounted(() => {
  verifyAndLoad();
});
</script>

<template>
  <div class="min-h-screen bg-surface text-on-surface antialiased">
    <Navbar />

    <!-- Confetti overlay -->
    <div
      v-if="showConfetti"
      class="pointer-events-none fixed inset-0 z-50 overflow-hidden"
    >
      <div
        v-for="i in 50"
        :key="i"
        class="absolute h-2 w-2 animate-bounce rounded-full"
        :style="{
          left: Math.random() * 100 + '%',
          top: '-2%',
          backgroundColor: ['#f59e0b','#10b981','#3b82f6','#ef4444','#8b5cf6'][i % 5],
          animationDelay: Math.random() * 2 + 's',
          animationDuration: 1 + Math.random() * 2 + 's',
        }"
      ></div>
    </div>

    <main class="mx-auto max-w-lg px-lg py-xl text-center">
      <!-- Verifying state -->
      <template v-if="verifying">
        <div class="mb-lg flex justify-center">
          <div class="rounded-full bg-primary/10 p-md">
            <RefreshCw class="h-16 w-16 text-primary animate-spin" />
          </div>
        </div>
        <h1 class="mb-xs text-3xl font-bold text-on-surface">Verifying Payment...</h1>
        <p class="mb-lg text-sm leading-relaxed text-on-surface-variant">
          Please wait while we confirm your payment.
        </p>
      </template>

      <!-- Success state -->
      <template v-else-if="contact">
        <div class="mb-lg flex justify-center">
          <div class="rounded-full bg-secondary/10 p-md">
            <CheckCircle2 class="h-16 w-16 text-secondary" />
          </div>
        </div>

        <h1 class="mb-xs text-3xl font-bold text-on-surface">Payment Successful!</h1>
        <p class="mb-lg text-sm leading-relaxed text-on-surface-variant">
          You've unlocked the candidate's contact information.
        </p>

        <section class="mb-lg rounded-2xl border border-outline-variant bg-card p-lg shadow-soft">
          <h2 class="mb-md text-lg font-semibold text-on-surface">Candidate Contact</h2>
          <div class="space-y-sm text-left">
            <div class="flex items-center gap-sm rounded-lg bg-surface-container-lowest px-md py-sm">
              <Mail class="h-5 w-5 text-primary" />
              <a :href="`mailto:${contact.email}`" class="text-sm text-primary hover:underline">
                {{ contact.email }}
              </a>
            </div>
            <div v-if="contact.phone" class="flex items-center gap-sm rounded-lg bg-surface-container-lowest px-md py-sm">
              <Phone class="h-5 w-5 text-primary" />
              <span class="text-sm text-on-surface">{{ contact.phone }}</span>
            </div>
          </div>
        </section>

        <div class="flex flex-col gap-sm">
          <Button
            :as="'a'"
            :href="`mailto:${contact.email}`"
            class="w-full gap-2"
          >
            <Mail class="h-4 w-4" />
            Contact Now
          </Button>
          <Button
            variant="outline"
            class="w-full gap-2"
            @click="router.push('/employer/applications')"
          >
            <ArrowLeft class="h-4 w-4" />
            Back to Applications
          </Button>
        </div>
      </template>

      <!-- Failed state -->
      <template v-else-if="!verifying && !contact">
        <div class="mb-lg flex justify-center">
          <div class="rounded-full bg-destructive/10 p-md">
            <XCircle class="h-16 w-16 text-destructive" />
          </div>
        </div>
        <h1 class="mb-xs text-3xl font-bold text-on-surface">Verification Failed</h1>
        <p class="mb-lg text-sm leading-relaxed text-on-surface-variant">
          We could not verify your payment. If you were charged, please contact support.
        </p>
        <Button
          variant="outline"
          class="w-full gap-2"
          @click="verifyAndLoad"
        >
          <RefreshCw class="h-4 w-4" />
          Try Again
        </Button>
      </template>
    </main>
    <Footer />
  </div>
</template>
