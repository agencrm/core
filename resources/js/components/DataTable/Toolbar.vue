<script setup lang="ts">

// resources/js/components/DataTable/Pagination.vue

import { ref, watch } from 'vue'
import debounce from 'lodash/debounce'

import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { ChevronFirst, ChevronLeft, ChevronRight, ChevronLast, RotateCcw, LayoutPanelLeft } from 'lucide-vue-next'

// Shad CN
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectTrigger,
  SelectContent,
  SelectItem,
  SelectValue,
} from '@/components/ui/select'
import {
  NumberField,
  NumberFieldContent,
  NumberFieldDecrement,
  NumberFieldIncrement,
  NumberFieldInput,
} from '@/components/ui/number-field'
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

const props = defineProps<{
  table: any
  loading?: boolean
  pagination: {
    pageIndex: number
    pageSize: number
  }
  totalPages: number
  rowCount: number
  totalRowCount: number
  setPageIndex: (index: number) => void
  setPageSize: (size: number) => void
  canPreviousPage: boolean
  canNextPage: boolean
    rowsLabel?: string
}>()


const emit = defineEmits<{
  (e: 'update:search', value: string): void
  (e: 'update:perPage', value: number): void
  (e: 'refresh'): void
}>()

const internalSearch = ref(props.search)

watch(() => props.search, val => {
  internalSearch.value = val
})

// ✅ Debounce emit
const debouncedSearch = debounce((val: string) => {
  emit('update:search', val)
}, 300)

watch(internalSearch, val => {
  debouncedSearch(val)
})

</script>

<template>

    <div class="flex flex-wrap justify-between items-center gap-2 py-2">
        <!-- Left Controls -->
        <div class="flex gap-2 items-center">
            <label for="perPageSelect" class="text-sm text-muted-foreground">
                {{ props.rowsLabel ?? 'Rows:' }}
            </label>

            <Select
                :value="pagination.pageSize.toString()"
                @update:modelValue="val => setPageSize(Number(val))"
            >
                <SelectTrigger
                    class="ghost-select"
                >
                    <SelectValue :placeholder="`${pagination.pageSize}`" />
                </SelectTrigger>

                <SelectContent class="bg-popover text-sm">
                    <SelectItem
                    v-for="size in [10, 20, 30, 40, 50]"
                    :key="size"
                    :value="size.toString()"
                    class="cursor-pointer hover:bg-muted"
                    >
                    {{ size }}
                    </SelectItem>
                </SelectContent>
            </Select>



            <Button
                variant="ghost"
                size="icon"
                class="h-9 w-9"
                @click="emit('refresh')"

            >
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
            :placeholder="`Search...`"
        />
        </div>

        
    </div>


</template>