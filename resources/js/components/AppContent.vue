<!-- resources/js/components/AppContent.vue -->
<script setup lang="ts">

// resources/js/components/AppContent.vue

import { SidebarInset } from '@/components/ui/sidebar';
import { computed } from 'vue';

interface Props {
  variant?: 'header' | 'sidebar';
  class?: string;
}

const props = defineProps<Props>();
const className = computed(() => props.class);
</script>

<template>
  <SidebarInset
    v-if="props.variant === 'sidebar'"
    :class="['flex flex-1 min-h-0 flex-col overflow-hidden', className]"
  >
    <slot />
  </SidebarInset>

  <main
    v-else
    :class="['mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-4 rounded-xl', className]"
  >
    <slot />
  </main>
</template>