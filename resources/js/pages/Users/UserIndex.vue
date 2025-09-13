<script setup lang="ts">
// resources/js/pages/Users/UserIndex.vue

import { ref, onMounted, watch, h } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { Plus, Settings2 } from 'lucide-vue-next'

import AppLayout from '@/layouts/AppLayout.vue'
import SettingsLayout from '@/layouts/settings/Layout.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import DataTable from '@/components/DataTable/DataTable.vue'


const apiKey = import.meta.env.VITE_APP_API_KEY

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'

import CreateElementForm from '@/components/Forms/CreateElement.vue'

// page state
const breadcrumbs = [{ title: 'Users', href: '/users' }]
const showBoardControls = ref(false)

const form = ref({
  name: '',
  description: '',
})

// fields for the create form
const fieldMap = [
  { key: 'name', type: 'text', label: 'Name', placeholder: 'User name' },
  { key: 'description', type: 'text', label: 'Description', placeholder: 'Describe the user' },
]

// view state
const routeKey = `viewMode:/users`
const view = ref<'table' | 'board'>('table')

onMounted(() => {
  try {
    const stored = localStorage.getItem(routeKey)
    if (stored === 'board' || stored === 'table') view.value = stored
  } catch {}
})
watch(view, val => {
  try { localStorage.setItem(routeKey, val) } catch {}
})

// users table columns
// const columns = [
//   { accessorKey: 'name', header: 'Name', cell: ({ row }) => row.getValue('name') },
//   { accessorKey: 'slug', header: 'Slug', cell: ({ row }) => row.getValue('slug') },
//   { accessorKey: 'status', header: 'Status', cell: ({ row }) => row.getValue('status') ?? 'draft' },
//   {
//     accessorKey: 'updated_at',
//     header: 'Updated',
//     cell: ({ row }) => new Date(row.getValue('updated_at')).toLocaleString(),
//   },
// ]

const columns = [
  {
    accessorKey: 'id',
    header: 'ID',
    cell: ({ row }) => {
      const id = row.original.id
      const href = (typeof route === 'function')
        ? route('settings.users.show', id)
        : `/users/${id}`
      return h(Link, { href, class: 'text-blue-600 hover:underline' }, () => String(id))
    },
  },
  {
    accessorKey: 'name',
    header: 'Name',
    cell: ({ row }) => {
      const id = row.original.id
      const name = row.getValue('name')
      const href = (typeof route === 'function')
        ? route('settings.users.show', id)
        : `/users/${id}`
      return h(Link, { href, class: 'text-blue-600 hover:underline' }, () => name)
    },
  },
  { accessorKey: 'email', header: 'email', cell: ({ row }) => row.getValue('email') },
  {
    accessorKey: 'created_at',
    header: 'Created',
    cell: ({ row }) => new Date(row.getValue('created_at')).toLocaleString(),
  },
]

function handleSuccess() {
  // optionally close dialog and reload table here (depends on your DataTable API)
}

function handleError(err: any) {
  console.error(err)
}
</script>

<template>
  <Head title="Users" />
  <AppLayout :breadcrumbs="breadcrumbs">


    <template #view-controls>
      <div class="flex justify-between items-center px-2 md:px-0">
        <button
          @click="showBoardControls = !showBoardControls"
          class="p-2 rounded hover:bg-accent hover:text-accent-foreground transition"
          title="Toggle Board Controls"
        >
          <Settings2 class="text-muted-foreground" />
        </button>
      </div>
    </template>

    <template #action-controls>
      <Dialog>
        <DialogTrigger>
          <Plus />
        </DialogTrigger>
        <DialogContent>
          <DialogHeader>
            <DialogTitle class="flex items-center justify-between border-b pb-3">
              Create A User
            </DialogTitle>
            <DialogDescription>
              <CreateElementForm
                :endpoint="'/api/users'"
                :fields="form"
                :field-map="fieldMap"
                :token="apiKey"
                :onSuccess="handleSuccess"
                :onError="handleError"
              />
            </DialogDescription>
          </DialogHeader>
        </DialogContent>
      </Dialog>
    </template>

        <SettingsLayout
        section-class="w-full space-y-12"
        section-wrapper-class="flex-1 w-full"
        >
            <!-- <div class="space-y-6">
            <HeadingSmall title="Appearance settings" description="Update your account's appearance settings" />
            </div>
            -->
            <DataTable
                endpoint-route="api.users.index"
                :columns="columns"
                search-placeholder="Search users..."
                auth-token="1|LgRGb6npouVszXCZDJcpGIVe6CVKS2CjhOBt1figbf15decf"
                v-slot:expand="{ row }"
            >
            <div class="p-2">
                <strong>Description:</strong> {{ row.original.description || '—' }}
            </div>
            </DataTable>

        </SettingsLayout>
    </AppLayout>
</template>
