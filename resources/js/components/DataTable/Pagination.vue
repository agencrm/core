<script setup lang="ts">

// resources/js/components/DataTable/Pagination.vue

import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { ChevronFirst, ChevronLeft, ChevronRight, ChevronLast } from 'lucide-vue-next'
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

defineProps<{
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
}>()
</script>

<template>
    <div class="mt-4 flex flex-wrap items-center gap-4 text-sm">

        <div class="inline-flex overflow-hidden rounded-md border border-input bg-transparent divide-x divide-border">
            <Button
                variant="ghost"
                size="sm"
                class="rounded-none"
                @click="setPageIndex(0)"
                :disabled="!canPreviousPage"
            >
                <ChevronFirst class="w-4 h-4" />
            </Button>
            <Button
                variant="ghost"
                size="sm"
                class="rounded-none"
                @click="setPageIndex(pagination.pageIndex - 1)"
                :disabled="!canPreviousPage"
            >
                <ChevronLeft class="w-4 h-4" />
            </Button>
            <Button
                variant="ghost"
                size="sm"
                class="rounded-none"
                @click="setPageIndex(pagination.pageIndex + 1)"
                :disabled="!canNextPage"
            >
                <ChevronRight class="w-4 h-4" />
            </Button>
            <Button
                variant="ghost"
                size="sm"
                class="rounded-none"
                @click="setPageIndex(totalPages - 1)"
                :disabled="!canNextPage"
            >
                <ChevronLast class="w-4 h-4" />
            </Button>
        </div>

        <div class="flex items-center gap-2">
            <Label for="page-number" class="text-muted-foreground">Page</Label>
            <NumberField
                id="page-number"
                :model-value="pagination.pageIndex + 1"
                :min="1"
                :max="totalPages"
                @update:modelValue="val => setPageIndex(Number(val) - 1)"
                class="w-20"
            >
                <NumberFieldContent class="h-8">
                <NumberFieldDecrement />
                <NumberFieldInput />
                <NumberFieldIncrement />
                </NumberFieldContent>
            </NumberField>
            <span class="text-muted-foreground">of {{ totalPages }}</span>
        </div>


        <div class="flex items-center gap-2">
            <span class="text-muted-foreground">Showing</span>

            <Select
                :value="pagination.pageSize.toString()"
                @update:modelValue="val => setPageSize(Number(val))"
                class="px-2 py-1 rounded border text-sm bg-transparent"
            >
                <SelectTrigger 
                    class="ghost-select"
                >
                    <SelectValue :placeholder="`${pagination.pageSize}`" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="size in [10, 20, 30, 40, 50]"
                        :key="size"
                        :value="size.toString()"
                    >
                        {{ size }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <span class="text-muted-foreground">of {{ totalRowCount }} rows</span>
        </div>
    </div>

</template>
