<script setup lang="ts">
// resources/js/components/Modal/Dialog.vue

import { Link } from '@inertiajs/vue3'
import {
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'

const props = defineProps<{
  id: number | string
  href?: string
  title?: string
  subtitle?: string
}>()

const emit = defineEmits<{ (e: 'close'): void }>()
</script>

<template>
  <DialogContent class="sm:max-w-lg">
    <DialogHeader class="flex items-center justify-between">
      <div class="w-full">
        <DialogTitle>{{ props.title ?? `Contact #${props.id}` }}</DialogTitle>
        <DialogDescription>
          {{ props.subtitle ?? 'Quick actions and details' }}
        </DialogDescription>
      </div>
      <!-- header toggle slot (e.g., Dialog/Tray switch) -->
      <slot name="headerExtra" />
    </DialogHeader>

    <!-- body -->
    <slot>
      <div class="space-y-3">
        <div class="text-sm text-muted-foreground">
          ID: <span class="font-medium text-foreground">{{ props.id }}</span>
        </div>
      </div>
    </slot>

    <!-- footer -->
    <div class="mt-6 flex items-center gap-2">
      <Link
        v-if="props.href"
        :href="props.href"
        class="inline-flex items-center rounded-md border px-3 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground"
        @click="emit('close')"
      >
        Open full view
      </Link>
      <button
        class="inline-flex items-center rounded-md border px-3 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground"
        @click="emit('close')"
      >
        Close
      </button>
    </div>
  </DialogContent>
</template>
