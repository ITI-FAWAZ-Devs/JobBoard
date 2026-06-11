<script setup lang="ts">
import { useRoute, useRouter } from "vue-router";
import { Button } from "@/components/ui/button";
import { XCircle, ArrowLeft, RefreshCw } from "lucide-vue-next";
import Navbar from "@/components/shared/Navbar.vue";
import Footer from "@/components/shared/Footer.vue";

const route = useRoute();
const router = useRouter();
const applicationId = Number(route.params.applicationId);
</script>

<template>
  <div class="min-h-screen bg-surface text-on-surface antialiased">
    <Navbar />

    <main class="mx-auto max-w-lg px-lg py-xl text-center">
      <div class="mb-lg flex justify-center">
        <div class="rounded-full bg-destructive/10 p-md">
          <XCircle class="h-16 w-16 text-destructive" />
        </div>
      </div>

      <h1 class="mb-xs text-3xl font-bold text-on-surface">Payment Cancelled</h1>
      <p class="mb-lg text-sm leading-relaxed text-on-surface-variant">
        The payment was cancelled or could not be completed. Your account has not been charged.
      </p>

      <div class="flex flex-col gap-sm">
        <Button
          class="w-full gap-2"
          @click="router.push(`/payment/checkout/${applicationId}`)"
        >
          <RefreshCw class="h-4 w-4" />
          Try Again
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
    </main>
    <Footer />
  </div>
</template>
