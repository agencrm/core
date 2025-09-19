<script setup lang="ts">
// resources/js/components/Modal/Tray.vue

import { Link } from '@inertiajs/vue3'
import {
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetDescription,
  SheetFooter,
} from '@/components/ui/sheet'

const props = defineProps<{
  id: number | string
  href?: string
  title?: string
  subtitle?: string
  contentClass?: string
}>()

const emit = defineEmits<{ (e: 'close'): void }>()
</script>

<template>
  <SheetContent side="right" :class="props.contentClass ?? 'w-[30rem] max-w-[90vw]'">
    <SheetHeader class="flex items-start justify-between">
      <div class="w-full">
        <SheetTitle>{{ props.title ?? `Contact #${props.id}` }}</SheetTitle>
        <SheetDescription>
          {{ props.subtitle ?? 'Quick actions and details' }}
        </SheetDescription>
      </div>
      <!-- header toggle slot (e.g., Dialog/Tray switch) -->
      <slot name="headerExtra" />
    </SheetHeader>

    <!-- body -->
    <div class="mt-4 space-y-3">
      <slot>
        <div class="text-sm text-muted-foreground">
          ID: <span class="font-medium text-foreground">{{ props.id }}</span>
        </div>
      </slot>
    </div>

    <!-- footer -->
    <SheetFooter class="mt-6 gap-2">
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
    </SheetFooter>
  </SheetContent>
</template>
