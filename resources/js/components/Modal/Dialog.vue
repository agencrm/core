<script setup lang="ts">
// resources/js/components/Modal/Dialog.vue

import { Link } from '@inertiajs/vue3'
import {
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
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
  // NEW
  blocks?: BlockSpec[]
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
      <slot name="headerExtra" />
    </DialogHeader>

    <!-- body -->
    <slot>
      <div class="space-y-3">
        <template v-if="props.blocks && props.blocks.length">
          <ModalBlockContent :id="props.id" :blocks="props.blocks" />
        </template>
        <template v-else>
          <div class="text-sm text-muted-foreground">
            ID: <span class="font-medium text-foreground">{{ props.id }}</span>
          </div>
        </template>
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
