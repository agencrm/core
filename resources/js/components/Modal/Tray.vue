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
import ModalBlockContent from '@/components/Modal/ModalBlockContent.vue'

type BlockKey = 'fields' | 'notes' | 'comments'
type BlockSpec =
  | BlockKey
  | {
      key: BlockKey
      title?: string
      props?: Record<string, any>
    }

const props = defineProps<{
  id: number | string
  href?: string
  title?: string
  subtitle?: string
  contentClass?: string
  // NEW
  blocks?: BlockSpec[]
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
      <slot name="headerExtra" />
    </SheetHeader>

    <!-- body -->
    <div class="mt-4 space-y-3">
      <slot>
        <template v-if="props.blocks && props.blocks.length">
          <ModalBlockContent :id="props.id" :blocks="props.blocks" />
        </template>
        <template v-else>
          <div class="text-sm text-muted-foreground">
            ID: <span class="font-medium text-foreground">{{ props.id }}</span>
          </div>
        </template>
      </slot>
    </div>

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
