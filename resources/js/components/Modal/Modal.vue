<!-- resources/js/components/Modal/Modal.vue -->
<script setup lang="ts">
// resources/js/components/Modal/Modal.vue

import { ref, computed, watch } from 'vue'
import { Dialog as ShadDialog, DialogTrigger } from '@/components/ui/dialog'
import { Sheet, SheetTrigger } from '@/components/ui/sheet'
import ModalDialog from '@/components/Modal/Dialog.vue'
import ModalTray from '@/components/Modal/Tray.vue'
import { SquareArrowOutUpRight } from 'lucide-vue-next'

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
  storageKey?: string
  // NEW: block list to control which accordions appear
  blocks?: BlockSpec[]
}>()

const prefKey = computed(() => props.storageKey ?? 'ui.modal.preferredStyle')
const preferred = ref<'dialog' | 'tray'>(readPref(prefKey.value))
const open = ref(false)

function readPref(key: string): 'dialog' | 'tray' {
  try {
    const v = localStorage.getItem(key)
    return v === 'tray' || v === 'dialog' ? v : 'dialog'
  } catch {
    return 'dialog'
  }
}
function writePref(key: string, v: 'dialog' | 'tray') {
  try {
    localStorage.setItem(key, v)
  } catch {}
}
watch(preferred, (v) => writePref(prefKey.value, v))

const ToggleControl = {
  props: ['modelValue'],
  emits: ['update:modelValue'],
  setup(p: any, { emit }: any) {
    const set = (v: 'dialog' | 'tray') => emit('update:modelValue', v)
    return { p, set }
  },
  template: `
    <div class="inline-flex rounded-md border overflow-hidden text-xs">
      <button type="button" class="px-2 py-1"
        :class="p.modelValue === 'dialog' ? 'bg-accent text-accent-foreground' : 'bg-background text-foreground/80 hover:bg-muted'"
        @click="set('dialog')">Dialog</button>
      <button type="button" class="px-2 py-1 border-l"
        :class="p.modelValue === 'tray' ? 'bg-accent text-accent-foreground' : 'bg-background text-foreground/80 hover:bg-muted'"
        @click="set('tray')">Tray</button>
    </div>
  `,
}
</script>

<template>
  <component :is="preferred === 'dialog' ? ShadDialog : Sheet" v-model:open="open">
    <!-- Trigger: always ours; slot provides label; icon always shown -->
    <component :is="preferred === 'dialog' ? DialogTrigger : SheetTrigger" as-child>
      <button
        type="button"
        class="inline-flex items-center gap-1 text-blue-600 hover:underline focus:outline-none"
      >
        <slot name="trigger">
          {{ String(id) }}
        </slot>
        <SquareArrowOutUpRight class="h-3 w-3 opacity-70" />
      </button>
    </component>

    <!-- Forward blocks to inner shells -->
    <ModalDialog
      v-if="preferred === 'dialog'"
      :id="id"
      :href="href"
      :title="title"
      :subtitle="subtitle"
      :blocks="blocks"
      @close="open = false"
    >
      <template #headerExtra>
        <ToggleControl v-model="preferred" />
      </template>
      <slot />
    </ModalDialog>

    <ModalTray
      v-else
      :id="id"
      :href="href"
      :title="title"
      :subtitle="subtitle"
      :content-class="contentClass"
      :blocks="blocks"
      @close="open = false"
    >
      <template #headerExtra>
        <ToggleControl v-model="preferred" />
      </template>
      <slot />
    </ModalTray>
  </component>
</template>
