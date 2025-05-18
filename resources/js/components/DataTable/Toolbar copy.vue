<script setup lang="ts">
// resources/js/components/DataTable/Toolbar.vue

import { ref, watch } from 'vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { RotateCcw, LayoutPanelLeft } from 'lucide-vue-next'

const props = defineProps<{
  search: string
  perPage: number
  perPageOptions?: number[]
  table: any
  loading?: boolean
  rowsLabel?: string
}>()

const emit = defineEmits<{
  (e: 'update:search', value: string): void
  (e: 'update:perPage', value: number): void
  (e: 'refresh'): void
}>()

const internalSearch = ref(props.search)

watch(internalSearch, (val) => {
  emit('update:search', val)
})

const handlePerPageChange = (e: Event) => {
  const value = parseInt((e.target as HTMLSelectElement).value)
  if (!isNaN(value)) emit('update:perPage', value)
}
</script>

<template>
  <div class="flex flex-wrap justify-between items-center gap-1 py-1">
    <!-- Left Controls -->
    <div class="flex gap-2 items-center">
      <label for="perPageSelect" class="text-sm text-muted-foreground">
        {{ props.rowsLabel ?? 'Rows:' }}
      </label>
      <select
        id="perPageSelect"
        :value="perPage"
        @change="handlePerPageChange"
        class="px-2 py-1 rounded border text-sm"
      >
        <option
          v-for="opt in perPageOptions ?? [10, 15, 25, 50, 100]"
          :key="opt"
          :value="opt"
        >
          {{ opt }}
        </option>
      </select>

      <Button variant="ghost" size="icon" class="h-9 w-9" @click="emit('refresh')" :disabled="loading">
        <RotateCcw class="h-4 w-4" />
      </Button>

      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <Button variant="ghost" size="icon" class="h-9 w-9">
            <LayoutPanelLeft class="h-4 w-4" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuCheckboxItem
            v-for="column in table.getAllColumns().filter(c => c.getCanHide())"
            :key="column.id"
            class="capitalize"
            :model-value="column.getIsVisible()"
            @update:model-value="val => column.toggleVisibility(!!val)"
          >
            {{ column.id }}
          </DropdownMenuCheckboxItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>

    <!-- Right Search -->
    <div class="flex gap-2 items-center">
      <Input
        v-model="internalSearch"
        class="max-w-52"
        :placeholder="'Search...'"
      />
    </div>
  </div>
</template>
