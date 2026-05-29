<script setup lang="ts">
import CTA from '@/components/shared/CTA.vue'
import FeaturedOpportunities from '@/components/shared/FeaturedOpportunities.vue'
import Footer from '@/components/shared/Footer.vue'
import Hero from '@/components/shared/Hero.vue'
import Navbar from '@/components/shared/Navbar.vue'
import Statistics from '@/components/shared/Statistics.vue'
</script>

<template>
  <div class="min-h-screen bg-surface text-on-surface antialiased">
    <Navbar />

    <main>
      <Hero />
      <Statistics />
      <FeaturedOpportunities />
      <CTA />
    </main>

    <Footer />
  </div>
</template>
