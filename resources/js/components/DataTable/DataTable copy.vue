<script setup lang="ts">
import {
  onMounted,
  ref,
  shallowRef,
  computed,
  watch,
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
  PaginationState,
} from '@tanstack/vue-table'
import {
  getCoreRowModel,
  getExpandedRowModel,
  getFilteredRowModel,
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
const loading = ref(false)
const responseDump = ref<any>(null)
const paginatedData = shallowRef<any[]>([])
const totalRows = ref(0)
const currentPage = ref(1)
const perPage = ref(15)

const pagination = ref<PaginationState>({
  pageIndex: 0,
  pageSize: perPage.value,
})

const builtUrl = computed(() => {
  const params = new URLSearchParams({
    page: String(pagination.value.pageIndex + 1),
    limit: String(pagination.value.pageSize),
    search: search.value || '',
    sort: sort.value || '',
    direction: direction.value || 'asc',
  })
  return `${route(props.routeName)}?${params.toString()}`
})

async function fetchData() {
  loading.value = true
  try {
    const page = pagination.value.pageIndex + 1
    const response = await axios.get(route(props.routeName), {
      params: {
        page,
        limit: pagination.value.pageSize,
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
    totalRows.value = response.data.meta?.total ?? rawData.length
    currentPage.value = response.data.meta?.page ?? page
    responseDump.value = response.data
  } catch (e) {
    console.error('Failed to load data:', e)
  } finally {
    loading.value = false
  }
}

watch(() => perPage.value, () => {
  pagination.value.pageIndex = 0
  pagination.value.pageSize = perPage.value
  fetchData()
})

watch(search, () => {
  pagination.value.pageIndex = 0
  fetchData()
})

const sorting = ref<SortingState>([])
const columnFilters = ref<ColumnFiltersState>([])
const columnVisibility = ref<VisibilityState>({})
const rowSelection = ref({})
const expanded = ref<ExpandedState>({})

function toggleSort(column: string) {
  if (sort.value === column) {
    direction.value = direction.value === 'asc' ? 'desc' : 'asc'
  } else {
    sort.value = column
    direction.value = 'asc'
  }
  fetchData()
}

const table = useVueTable({
  data: paginatedData,
  columns: props.columns ?? [],
  manualPagination: true,
  pageCount: computed(() => Math.ceil(totalRows.value / pagination.value.pageSize)),
  state: {
    pagination: pagination.value,
    sorting: sorting.value,
    columnFilters: columnFilters.value,
    columnVisibility: columnVisibility.value,
    rowSelection: rowSelection.value,
    expanded: expanded.value,
  },
  onPaginationChange: updater => {
    valueUpdater(updater, pagination)
    fetchData()
  },
  onSortingChange: val => {
    valueUpdater(val, sorting)
    const firstSort = val[0]
    if (firstSort) {
      sort.value = firstSort.id
      direction.value = firstSort.desc ? 'desc' : 'asc'
    } else {
      sort.value = ''
      direction.value = 'asc'
    }
    fetchData()
  },
  onColumnFiltersChange: val => valueUpdater(val, columnFilters),
  onColumnVisibilityChange: val => valueUpdater(val, columnVisibility),
  onRowSelectionChange: val => valueUpdater(val, rowSelection),
  onExpandedChange: val => valueUpdater(val, expanded),
  getCoreRowModel: getCoreRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
  getExpandedRowModel: getExpandedRowModel(),
})

onMounted(() => fetchData())
</script>

<template>
  <div class="p-4">

    <pre class="mb-4 text-xs bg-muted p-4 rounded overflow-auto max-h-32">
      {{ builtUrl }}
    </pre>

    <DataTableToolbar
      :search="search"
      :per-page="perPage"
      :per-page-options="[10, 25, 50]"
      :rows-label="'Items:'"
      :table="table"
      @update:search="search = $event"
      @update:perPage="val => perPage.value = val"
      @refresh="fetchData"
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

    <div v-if="!loading && table.getPageCount() > 1" class="my-4 flex gap-2 items-center">
      <Button :disabled="pagination.value.pageIndex === 0" @click="table.previousPage()">Previous</Button>
      <Button :disabled="pagination.value.pageIndex + 1 === table.getPageCount()" @click="table.nextPage()">Next</Button>
      <span class="text-sm text-muted-foreground">
        Page {{ pagination.value.pageIndex + 1 }} of {{ table.getPageCount() }}
      </span>
    </div>

    <pre v-if="responseDump" class="mt-4 text-xs bg-muted p-4 rounded overflow-auto resize-y max-h-80">
      {{ responseDump }}
    </pre>
  </div>
</template>
