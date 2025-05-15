<script setup lang="ts">

// resources/js/pages/Messages.vue

import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'

import type { BreadcrumbItem } from '@/types'
import { h } from 'vue'
import type { ColumnDef } from '@tanstack/vue-table'

//
import PlaceholderPattern from '@/components/PlaceholderPattern.vue'
import DataTable from '@/components/DataTable/DataTable.vue'
import { Button } from '@/components/ui/button'
import CellActions from '@/components/DataTable/CellActions.vue'


const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Messages',
    href: '/messages',
  },
]

interface MessageEntry {
  id: number
  name: string
  mime_type: string
  size: number
  path: string
  created_at: string
}

const columns: ColumnDef<MessageEntry>[] = [
  {
    accessorKey: 'sender',
    header: 'Sender',
    cell: ({ row }) => row.getValue('sender'),
  },
  {
    accessorKey: 'recipient',
    header: 'Recipient',
    cell: ({ row }) => row.getValue('recipient'),
  },
  {
    accessorKey: 'created_at',
    header: 'Created At',
    cell: ({ row }) => new Date(row.getValue('created_at')).toLocaleString(),
  },
  {
    id: 'data',
    header: 'Data (Child Row)',
    enableHiding: false,
    cell: ({ row }) =>
      h(Button, {
        variant: 'ghost',
        onClick: () => row.toggleExpanded(),
      }, () => row.getIsExpanded() ? 'Collapse' : 'Expand'),
  },
  {
    id: 'actions',
    header: 'Actions',
    enableHiding: false,
    cell: ({ row }) =>
      h(CellActions, {
        row: row.original
      }),
  },
]
</script>

<template>
  <Head title="Messages" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <div
          v-for="n in 3"
          :key="n"
          class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
          <PlaceholderPattern />
        </div>
      </div>

      <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
        <DataTable
          route-name="api.messages.index"
          :columns="columns"
          search-placeholder="Search messages..."
          auth-token="1|LgRGb6npouVszXCZDJcpGIVe6CVKS2CjhOBt1figbf15decf"
          v-slot:expand="{ row }"
        >
          <div class="p-2">
            <strong>Path:</strong> {{ row.original.path }}<br />
            <strong>Size:</strong> {{ (row.original.size / 1024).toFixed(2) }} KB
          </div>
        </DataTable>
      </div>
    </div>
  </AppLayout>
</template>
