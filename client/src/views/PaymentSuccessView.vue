<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { Button } from "@/components/ui/button";
import { toast } from "vue-sonner";
import { CheckCircle2, Mail, Phone, ArrowLeft, RefreshCw, XCircle } from "lucide-vue-next";
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

const confettiParticles = ref<Array<{
  left: string;
  delay: string;
  duration: string;
  color: string;
  size: string;
}>>([]);

function generateConfetti() {
  const colors = ["#f59e0b", "#10b981", "#3b82f6", "#ef4444", "#8b5cf6"];
  const sizes = ["6px", "8px", "10px", "12px"];
  confettiParticles.value = Array.from({ length: 60 }).map((_, i) => {
    const color = colors[i % colors.length] as string;
    const size = sizes[i % sizes.length] as string;
    return {
      left: `${Math.random() * 100}%`,
      delay: `${Math.random() * 3}s`,
      duration: `${2 + Math.random() * 3}s`,
      color,
      size,
    };
  });
}

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
    generateConfetti();
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
    }, 6000);
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
  <div class="min-h-screen bg-gradient-mesh text-on-background antialiased flex flex-col">
    <Navbar />

    <!-- Confetti overlay -->
    <div
      v-if="showConfetti"
      class="pointer-events-none fixed inset-0 z-50 overflow-hidden"
    >
      <div
        v-for="(particle, idx) in confettiParticles"
        :key="idx"
        class="confetti-particle"
        :style="{
          left: particle.left,
          animationDelay: particle.delay,
          animationDuration: particle.duration,
          backgroundColor: particle.color,
          width: particle.size,
          height: particle.size,
        }"
      ></div>
    </div>

    <main class="flex-grow flex items-center justify-center p-md py-xl">
      <div class="w-full max-w-3xl bg-card border border-border shadow-elegant rounded-3xl p-lg md:p-xl relative overflow-hidden transition-all duration-300 hover:shadow-glow">
        <!-- Decorative subtle gradient glow -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-64 h-64 bg-primary/5 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Verifying state -->
        <div v-if="verifying" class="text-center relative z-10 py-lg">
          <div class="mb-md flex justify-center">
            <div class="relative flex items-center justify-center">
              <div class="absolute h-20 w-20 rounded-full border-4 border-primary/20 animate-pulse"></div>
              <div class="absolute h-16 w-16 rounded-full border-t-4 border-primary animate-spin"></div>
              <RefreshCw class="h-8 w-8 text-primary relative z-10 animate-pulse" />
            </div>
          </div>
          <h1 class="font-headline-md text-headline-md text-on-background mb-xs tracking-tight">Verifying Payment</h1>
          <p class="font-body-md text-body-md text-on-surface-variant max-w-xs mx-auto leading-relaxed">
            Please wait a moment while we confirm your payment transaction.
          </p>
        </div>

        <!-- Success state -->
        <div v-else-if="contact" class="text-center relative z-10">
          <div class="mb-md flex justify-center">
            <div class="relative flex items-center justify-center">
              <div class="absolute h-20 w-20 rounded-full bg-secondary/15 animate-ping"></div>
              <div class="relative rounded-full bg-secondary-container/20 p-md text-secondary shadow-[0_0_20px_rgba(0,107,95,0.2)]">
                <CheckCircle2 class="h-12 w-12 text-secondary" />
              </div>
            </div>
          </div>

          <h1 class="font-headline-md text-headline-md text-on-background mb-xs tracking-tight">Payment Successful!</h1>
          <p class="font-body-md text-body-md text-on-surface-variant mb-lg leading-relaxed">
            You have successfully unlocked the candidate's contact information.
          </p>

          <div class="mb-lg rounded-2xl border border-outline-variant bg-surface p-md text-left shadow-soft hover:shadow-md transition-shadow duration-300">
            <h2 class="font-label-md text-label-md text-on-surface-variant mb-sm uppercase tracking-wider">Unlocked Details</h2>
            <div class="space-y-sm">
              <div class="flex items-center gap-sm bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-md py-sm transition-colors hover:border-primary/50 group">
                <div class="p-xs rounded-lg bg-primary/10 text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
                  <Mail class="h-4 w-4 flex-shrink-0" />
                </div>
                <div class="min-w-0 flex-1">
                  <div class="text-[11px] text-outline uppercase tracking-wider font-semibold">Email Address</div>
                  <a :href="`mailto:${contact.email}`" class="text-body-sm font-body-sm text-primary hover:underline truncate block font-medium">
                    {{ contact.email }}
                  </a>
                </div>
              </div>
              
              <div v-if="contact.phone" class="flex items-center gap-sm bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-md py-sm transition-colors hover:border-primary/50 group">
                <div class="p-xs rounded-lg bg-primary/10 text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
                  <Phone class="h-4 w-4 flex-shrink-0" />
                </div>
                <div class="min-w-0 flex-1">
                  <div class="text-[11px] text-outline uppercase tracking-wider font-semibold">Phone Number</div>
                  <span class="text-body-sm font-body-sm text-on-surface block font-medium">
                    {{ contact.phone }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="flex flex-col gap-sm">
            <Button
              as="a"
              :href="`mailto:${contact.email}`"
              class="w-full font-label-md text-label-md cursor-pointer justify-center py-sm shadow-soft hover:shadow-elegant transition-all duration-300"
            >
              <Mail class="h-4 w-4 mr-2" />
              Send Email
            </Button>
            <Button
              variant="outline"
              class="w-full font-label-md text-label-md cursor-pointer justify-center py-sm"
              @click="router.push('/employer/applications')"
            >
              <ArrowLeft class="h-4 w-4 mr-2" />
              Back to Applications
            </Button>
          </div>
        </div>

        <!-- Failed state -->
        <div v-else class="text-center relative z-10">
          <div class="mb-md flex justify-center">
            <div class="relative flex items-center justify-center">
              <div class="absolute h-20 w-20 rounded-full bg-destructive/15 animate-pulse"></div>
              <div class="relative rounded-full bg-error-container/20 p-md text-destructive shadow-[0_0_20px_rgba(186,26,26,0.2)]">
                <XCircle class="h-12 w-12 text-destructive" />
              </div>
            </div>
          </div>
          <h1 class="font-headline-md text-headline-md text-on-background mb-xs tracking-tight">Verification Failed</h1>
          <p class="font-body-md text-body-md text-on-surface-variant mb-lg leading-relaxed max-w-sm mx-auto">
            We could not verify your payment. If you were charged, please contact our support team.
          </p>

          <div class="mb-lg rounded-2xl border border-destructive/20 bg-error-container/10 p-md text-left text-body-sm text-on-surface-variant">
            <p class="font-medium text-destructive mb-xs">What should I do?</p>
            <ul class="list-disc list-inside space-y-1 text-xs text-on-surface-variant">
              <li>Wait a few seconds and try clicking "Try Again".</li>
              <li>Check your bank account/card statements.</li>
              <li>Contact support with your transaction reference.</li>
            </ul>
          </div>

          <div class="flex flex-col gap-sm">
            <Button
              class="w-full font-label-md text-label-md cursor-pointer justify-center py-sm"
              @click="verifyAndLoad"
            >
              <RefreshCw class="h-4 w-4 mr-2" />
              Try Again
            </Button>
            <Button
              variant="outline"
              class="w-full font-label-md text-label-md cursor-pointer justify-center py-sm"
              @click="router.push('/employer/applications')"
            >
              <ArrowLeft class="h-4 w-4 mr-2" />
              Back to Applications
            </Button>
          </div>
        </div>
      </div>
    </main>

    <Footer />
  </div>
</template>

<style scoped>
@keyframes fall {
  0% {
    transform: translateY(-50px) rotate(0deg);
    opacity: 1;
  }
  100% {
    transform: translateY(100vh) rotate(360deg);
    opacity: 0;
  }
}

.confetti-particle {
  position: absolute;
  top: -10px;
  border-radius: 50%;
  animation: fall linear infinite;
}
</style>
