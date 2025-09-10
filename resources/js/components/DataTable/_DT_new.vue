<script setup lang="ts">

// resources/js/components/DataTable/_DT_new.vue

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
import { getCoreRowModel, useVueTable, FlexRender } from '@tanstack/vue-table'
import axios from 'axios'
import { route } from 'ziggy-js'

interface Post {
  userId: number
  id: number
  title: string
  body: string
}

const columns = [
  { accessorKey: 'id', header: 'Id' },
  { accessorKey: 'userId', header: 'User Id' },
  { accessorKey: 'title', header: 'Title' },
  { accessorKey: 'body', header: 'Body' },
]

const data = ref<Post[]>([])
const total = ref(100)
const pagination = ref({ pageIndex: 0, pageSize: 10 })
const table = shallowRef()

const apiKey = import.meta.env.APP_API_KEY

const token = apiKey;

const buildRequestUrl_v1 = (pageIndex: number, pageSize: number): string => {
  const baseUrl = 'https://jsonplaceholder.typicode.com/posts'
  const params = new URLSearchParams()

  params.set('_start', String(pageIndex * pageSize))
  params.set('_limit', String(pageSize))

  console.log(`${baseUrl}?${params.toString()}`);

  return `${baseUrl}?${params.toString()}`
}


// ✅ Updates for Laravel-style API: ?page=1&limit=15&search=&sort=&direction=asc
const buildRequestUrl = (pageIndex: number, pageSize: number): string => {
  const baseUrl = 'http://127.0.0.1:8000/api/labels'
  const params = new URLSearchParams()

  params.set('page', String(pageIndex + 1)) // Laravel is 1-based
  params.set('limit', String(pageSize))
  params.set('search', '')     // Optional but include if needed
  params.set('sort', '')       // Optional
  params.set('direction', 'asc') // Optional

  const fullUrl = `${baseUrl}?${params.toString()}`
  console.log(fullUrl)
  return fullUrl
}



async function getPageData(pageIndex: number, pageSize: number) {
  const url = buildRequestUrl(pageIndex, pageSize)

  const headers = token ? { Authorization: `Bearer ${token}` } : {}

  const res = await axios.get(url, { headers })

  total.value = res.data.meta.total
  data.value = res.data.data


  console.log('pageIndex', pageIndex);
  console.log('pageSize', pageSize)
  console.log('data', data.value);

table.value = useVueTable({
  data: data.value,
  columns,
  getCoreRowModel: getCoreRowModel(),
  manualPagination: true,
  pageCount: Math.ceil(total.value / pageSize),

  // ✅ This makes pagination reactive
  state: {
    get pagination() {
      return pagination.value
    },
  },

  onPaginationChange: (updater) => {
    pagination.value =
      typeof updater === 'function' ? updater(pagination.value) : updater
  },
})



}



watchEffect(() => {
  getPageData(pagination.value.pageIndex, pagination.value.pageSize)
})
</script>

<template>
  <div class="p-4" v-if="table">
    <table class="w-full border-collapse border">
      <thead>
        <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
          <th
            v-for="header in headerGroup.headers"
            :key="header.id"
            class="border p-2"
          >
            <template v-if="!header.isPlaceholder">
              <FlexRender
                :render="header.column.columnDef.header"
                :props="header.getContext()"
              />
            </template>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in table.getRowModel().rows" :key="row.id">
          <td
            v-for="cell in row.getVisibleCells()"
            :key="cell.id"
            class="border p-2"
          >
            <FlexRender
              :render="cell.column.columnDef.cell"
              :props="cell.getContext()"
            />
          </td>
        </tr>
      </tbody>
    </table>

    <div class="mt-4 flex gap-2 items-center">
      <button
        class="border px-2 py-1 rounded"
        @click="table.setPageIndex(0)"
        :disabled="!table.getCanPreviousPage()"
      >&laquo;</button>
      <button
        class="border px-2 py-1 rounded"
        @click="table.previousPage()"
        :disabled="!table.getCanPreviousPage()"
      >&lsaquo;</button>
      <button
        class="border px-2 py-1 rounded"
        @click="table.nextPage()"
        :disabled="!table.getCanNextPage()"
      >&rsaquo;</button>
      <button
        class="border px-2 py-1 rounded"
        @click="table.setPageIndex(table.getPageCount() - 1)"
        :disabled="!table.getCanNextPage()"
      >&raquo;</button>

      <span>Page {{ pagination.pageIndex + 1 }} of {{ table.getPageCount() }}</span>

      <input
        type="number"
        class="border px-2 py-1 rounded w-16"
        :value="pagination.pageIndex + 1"
        @change="e => table.setPageIndex(Number(e.target.value) - 1)"
      />

      <select
        class="border px-2 py-1 rounded"
        :value="pagination.pageSize"
        @change="e => table.setPageSize(Number(e.target.value))"
      >
        <option v-for="size in [10, 20, 30, 40, 50]" :key="size" :value="size">
          Show {{ size }}
        </option>
      </select>
    </div>

    <div class="mt-2 text-sm text-gray-500">
      Showing {{ table.getRowModel().rows.length }} rows
    </div>
  </div>
</template>
