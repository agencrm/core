<script setup lang="ts">
// resources/js/components/Views/ViewController.vue

import { defineProps, defineEmits } from 'vue'
import { Table, Columns2 } from 'lucide-vue-next'

const props = defineProps<{ view: 'table' | 'board' }>()
const emit = defineEmits<{
  (e: 'update:view', val: 'table' | 'board'): void
}>()

const baseClasses =
  'inline-flex items-center justify-center rounded-md px-3 py-2 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none ring-offset-background'


</script>

<template>
  <div class="flex items-center gap-2 p-2">
    <button
      @click="emit('update:view', 'table')"
      :class="[
        baseClasses,
        view === 'table'
          ? 'bg-primary text-primary-foreground hover:bg-primary/90'
          : 'bg-muted text-muted-foreground hover:bg-muted/80'
      ]"
    >
      <Table class="w-4 h-4" />
    </button>

    <button
      @click="emit('update:view', 'board')"
      :class="[
        baseClasses,
        view === 'board'
          ? 'bg-primary text-primary-foreground hover:bg-primary/90'
          : 'bg-muted text-muted-foreground hover:bg-muted/80'
      ]"
    >
      <Columns2 class="w-4 h-4" />
    </button>
  </div>
</template>
