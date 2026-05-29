<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { DropdownMenuItem, type DropdownMenuItemProps } from 'reka-ui'
import { cn } from '@/lib/utils'

const props = defineProps<DropdownMenuItemProps & { class?: HTMLAttributes['class'] }>()
</script>

<template>
  <DropdownMenuItem
    v-bind="props"
    :class="cn('relative flex cursor-pointer select-none items-center gap-xs rounded px-sm py-xs font-body-sm text-body-sm text-on-surface outline-none transition-colors hover:bg-surface-container-low focus:bg-surface-container-low data-[disabled]:pointer-events-none data-[disabled]:opacity-50', props.class)"
  >
    <slot />
  </DropdownMenuItem>
</template>
