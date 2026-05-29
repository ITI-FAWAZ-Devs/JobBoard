<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { DropdownMenuContent, DropdownMenuPortal, type DropdownMenuContentEmits, type DropdownMenuContentProps, useForwardPropsEmits } from 'reka-ui'
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<DropdownMenuContentProps & { class?: HTMLAttributes['class'] }>(), {
  sideOffset: 8,
})
const emits = defineEmits<DropdownMenuContentEmits>()

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
  <DropdownMenuPortal>
    <DropdownMenuContent
      v-bind="forwarded"
      :class="cn('z-50 min-w-48 overflow-hidden rounded-lg border border-outline-variant bg-surface-container-lowest p-xs text-on-surface shadow-[0px_12px_24px_rgba(0,0,0,0.12)] data-[state=open]:animate-in data-[state=closed]:animate-out', props.class)"
    >
      <slot />
    </DropdownMenuContent>
  </DropdownMenuPortal>
</template>
