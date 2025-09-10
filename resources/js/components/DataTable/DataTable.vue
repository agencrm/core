<script setup lang="ts">

// resources/js/components/DataTable/DataTable.vue

import {
  ref,
  shallowRef,
  watch,
  watchEffect,
  defineProps,
  defineExpose, 
} from 'vue'

import axios from 'axios'
import { route } from 'ziggy-js'

import type {
  ColumnDef,
  SortingState,
  VisibilityState,
  ExpandedState,
  ColumnFiltersState,
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

import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'

//
import DataTablePagination from '@/components/DataTable/Pagination.vue'
import DataTableToolbar from '@/components/DataTable/Toolbar.vue'


// Props
const props = defineProps<{
  endpointRoute: string
  authToken?: string
  columns: ColumnDef<any>[]
  searchPlaceholder?: string
}>()

// Data + state
const data = ref<any[]>([])
const total = ref(0)
const pagination = ref({ pageIndex: 0, pageSize: 20 })
const searchTerm = ref('')
const perPage = ref(pagination.value.pageSize)
const table = shallowRef()

// Build Ziggy-powered request URL
const buildRequestUrl = (pageIndex: number, pageSize: number): string => {
    const baseUrl = route(props.endpointRoute)
    const params = new URLSearchParams()

    params.set('page', String(pageIndex + 1))
    params.set('limit', String(pageSize))
    params.set('search', searchTerm.value)
    params.set('sort', '')
    params.set('direction', 'asc')

    const fullUrl = `${baseUrl}?${params.toString()}`
    console.log('[URL]', fullUrl)
    return fullUrl
}

// Fetch data
async function getPageData(pageIndex: number, pageSize: number) {
  const url = buildRequestUrl(pageIndex, pageSize)
  const headers = props.authToken ? { Authorization: `Bearer ${props.authToken}` } : {}

  const res = await axios.get(url, { headers })

  data.value = res.data.data
  total.value = res.data.meta.total

  table.value = useVueTable({
    data: data.value,
    columns: props.columns,
    manualPagination: true,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getExpandedRowModel: getExpandedRowModel(),

    pageCount: Math.ceil(total.value / pageSize),

    state: {
      get pagination() {
        return pagination.value
      }
    },

    onPaginationChange: (updater) => {
      pagination.value =
        typeof updater === 'function' ? updater(pagination.value) : updater
    },
  })
}

// Watch pagination
watchEffect(() => {
  getPageData(pagination.value.pageIndex, pagination.value.pageSize)
})

// Watch search term
watch([searchTerm, perPage], () => {
  pagination.value.pageIndex = 0
  pagination.value.pageSize = perPage.value
  getPageData(0, perPage.value)
})


// === NEW: util to resync TanStack with our updated data without a fetch
function syncTableData() {
  if (!table.value) return
  table.value.setOptions((prev: any) => ({
    ...prev,
    data: data.value,
    pageCount: Math.ceil(total.value / pagination.value.pageSize),
  }))
}

// === NEW: exposed helpers for optimistic updates ===
function prependRow(row: any) {
  data.value = [row, ...data.value]
  total.value = (total.value || 0) + 1
  // keep the user on the current page; optionally jump to first page:
  // pagination.value.pageIndex = 0
  syncTableData()
}

function appendRow(row: any) {
  data.value = [...data.value, row]
  total.value = (total.value || 0) + 1
  syncTableData()
}

// Optional helpers if you want them later:
function updateRow(id: any, patch: Record<string, any>) {
  const idx = data.value.findIndex(r => r.id === id)
  if (idx !== -1) {
    data.value[idx] = { ...data.value[idx], ...patch }
    syncTableData()
  }
}
function removeRow(id: any) {
  const before = data.value.length
  data.value = data.value.filter(r => r.id !== id)
  if (data.value.length !== before) total.value = Math.max(0, total.value - 1)
  syncTableData()
}

// Expose to parent
defineExpose({ prependRow, appendRow, updateRow, removeRow })


</script>

<template>
    <div class="p-4" v-if="table">

        <DataTableToolbar

            :pagination="pagination"
            :totalPages="table.getPageCount()"
            :rowCount="table.getRowModel().rows.length"
            :totalRowCount="total"
            :canPreviousPage="table.getCanPreviousPage()"
            :canNextPage="table.getCanNextPage()"
            :setPageIndex="table.setPageIndex"
            :setPageSize="table.setPageSize"
            :search="searchTerm"
            :table="table"
            @update:search="searchTerm = $event"
            @update:perPage="(val) => { perPage.value = val }"
            @refresh="getPageData(pagination.pageIndex, pagination.pageSize)"
        />

        <Table>
            <TableHeader>
                <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                <TableHead v-for="header in headerGroup.headers" :key="header.id">
                    <template v-if="!header.isPlaceholder">
                    <FlexRender
                        :render="header.column.columnDef.header"
                        :props="header.getContext()"
                    />
                    </template>
                </TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="row in table.getRowModel().rows" :key="row.id">
                <TableCell
                    v-for="cell in row.getVisibleCells()"
                    :key="cell.id"
                    class="border p-2"
                >
                    <FlexRender
                    :render="cell.column.columnDef.cell"
                    :props="cell.getContext()"
                    />
                </TableCell>
                </TableRow>
            </TableBody>
        </Table>

        <DataTablePagination
            :pagination="pagination"
            :totalPages="table.getPageCount()"
            :rowCount="table.getRowModel().rows.length"
            :totalRowCount="total"
            :canPreviousPage="table.getCanPreviousPage()"
            :canNextPage="table.getCanNextPage()"
            :setPageIndex="table.setPageIndex"
            :setPageSize="table.setPageSize"
        />

    </div>
</template>