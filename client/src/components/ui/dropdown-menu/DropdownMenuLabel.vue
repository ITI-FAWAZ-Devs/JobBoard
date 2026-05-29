<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: HTMLAttributes['class'] }>()
</script>

<template>
  <div :class="cn('px-sm py-xs font-label-sm text-label-sm text-on-surface-variant', props.class)">
    <slot />
  </div>
</template>
