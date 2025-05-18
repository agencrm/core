<script setup lang="ts">
import {
  onMounted,
  ref,
  shallowRef,
  computed,
  watch,
   watchEffect, 
  useSlots,
  defineProps,
  defineEmits,
} from 'vue'
import axios from 'axios'
import { route } from 'ziggy-js'
import type {
  ColumnDef,
  ColumnFiltersState,
  ExpandedState,
  SortingState,
  VisibilityState,
} from '@tanstack/vue-table'
import {
  getCoreRowModel,
  getExpandedRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useVueTable,
  FlexRender,
} from '@tanstack/vue-table'

import { valueUpdater } from '@/utils'
import { Button } from '@/components/ui/button'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import DataTableToolbar from '@/components/DataTable/Toolbar.vue'

const props = defineProps<{
  routeName: string
  columns: ColumnDef<any>[]
  rowKey?: keyof any
  searchPlaceholder?: string
  authToken?: string
}>()

const emit = defineEmits(['update:data', 'update:labelMap'])

const search = ref('')
const sort = ref('')
const direction = ref<'asc' | 'desc'>('asc')
const currentPage = ref(1)
const lastPage = ref(1)
const perPage = ref(15)
const loading = ref(false)
const responseDump = ref<any>(null)
//const paginatedData = shallowRef<any[]>([])
const paginatedData = ref<any[]>([])

// ✅ Show actual request URL
const builtUrl = computed(() => {
  const params = new URLSearchParams({
    page: String(currentPage.value),
    limit: String(perPage.value),
    search: search.value || '',
    sort: sort.value || '',
    direction: direction.value || 'asc',
  })
  return `${route(props.routeName)}?${params.toString()}`
  
})

async function fetchData(page = 1) {
  loading.value = true
  try {
    const response = await axios.get(route(props.routeName), {
      params: {
        page,
        limit: perPage.value,
        search: search.value,
        sort: sort.value,
        direction: direction.value,
      },
      headers: {
        Authorization: props.authToken ? `Bearer ${props.authToken}` : undefined,
      },
      withCredentials: true,
    })

    const rawData = response.data?.data ?? []
    const labels = response.data?.sideloaded?.labels ?? []

    emit('update:data', rawData)
    emit('update:labelMap', Object.fromEntries(labels.map(label => [Number(label.id), label])))

    paginatedData.value = rawData
    currentPage.value = response.data.meta?.current_page || 1
    lastPage.value = response.data.meta?.last_page || 1
    responseDump.value = response.data
  } catch (e) {
    console.error('Failed to load data:', e)
  } finally {
    loading.value = false
  }
}

onMounted(() => fetchData())
watch(search, () => fetchData(1))

function toggleSort(column: string) {
  if (sort.value === column) {
    direction.value = direction.value === 'asc' ? 'desc' : 'asc'
  } else {
    sort.value = column
    direction.value = 'asc'
  }
  fetchData(currentPage.value)
}

const sorting = ref<SortingState>([])
const columnFilters = ref<ColumnFiltersState>([])
const columnVisibility = ref<VisibilityState>({})
const rowSelection = ref({})
const expanded = ref<ExpandedState>({})


const table = ref()

watchEffect(() => {
  table.value = useVueTable({
    data: paginatedData.value, // pass plain value
    columns: props.columns ?? [],
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getExpandedRowModel: getExpandedRowModel(),
    onSortingChange: val => valueUpdater(val, sorting),
    onColumnFiltersChange: val => valueUpdater(val, columnFilters),
    onColumnVisibilityChange: val => valueUpdater(val, columnVisibility),
    onRowSelectionChange: val => valueUpdater(val, rowSelection),
    onExpandedChange: val => valueUpdater(val, expanded),
    state: {
      get sorting() { return sorting.value },
      get columnFilters() { return columnFilters.value },
      get columnVisibility() { return columnVisibility.value },
      get rowSelection() { return rowSelection.value },
      get expanded() { return expanded.value },
    },
  })
})

</script>

<template>
  <div class="p-4">

    <pre class="mb-4 text-xs bg-muted p-4 rounded overflow-auto max-h-32">
      {{ builtUrl }}
    </pre>
    <pre class="mb-4 text-xs bg-muted p-4 rounded overflow-auto max-h-32">
      {{ paginatedData }}
    </pre>




    <DataTableToolbar
      :search="search"
      :per-page="perPage"
      :per-page-options="[10, 25, 50]"
      :rows-label="'Items:'"
      :table="table"
      @update:search="search = $event"
      @update:perPage="(val) => { perPage = val; fetchData(1) }"
      @refresh="fetchData(currentPage)"
    />

    <div class="my-6 border rounded">
      <Table>
        <TableHeader>
          <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
            <TableHead v-for="header in headerGroup.headers" :key="header.id">
              <FlexRender
                v-if="!header.isPlaceholder"
                :render="header.column.columnDef.header"
                :props="header.getContext()"
              />
            </TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <template v-if="table.getRowModel().rows?.length">
            <template v-for="row in table.getRowModel().rows" :key="row.id">
              <TableRow :data-state="row.getIsSelected() && 'selected'">
                <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                  <FlexRender
                    :render="cell.column.columnDef.cell"
                    :props="cell.getContext()"
                  />
                </TableCell>
              </TableRow>
              <TableRow v-if="row.getIsExpanded()">
                <TableCell
                  :colspan="props.columns?.length || 1"
                  class="bg-muted text-sm text-muted-foreground"
                >
                  <slot name="expand" :row="row">
                    {{ JSON.stringify(row.original, null, 2) }}
                  </slot>
                </TableCell>
              </TableRow>
            </template>
          </template>
          <TableRow v-else>
            <TableCell :colspan="props.columns?.length || 1" class="text-center">
              No results.
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <div v-if="!loading && lastPage > 1" class="my-4 flex gap-2 items-center">
      <Button :disabled="currentPage === 1" @click="fetchData(currentPage - 1)">Previous</Button>
      <Button :disabled="currentPage === lastPage" @click="fetchData(currentPage + 1)">Next</Button>
      <span class="text-sm text-muted-foreground">
        Page {{ currentPage }} of {{ lastPage }}
      </span>
    </div>

    <pre
      v-if="responseDump"
      class="mt-4 text-xs bg-muted p-4 rounded overflow-auto resize-y max-h-80"
    >
      {{ responseDump }}
    </pre>
  </div>
</template>
