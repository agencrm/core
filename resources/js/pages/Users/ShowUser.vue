<script setup lang="ts">
// resources/js/pages/Users/ShowUser.vue

import { ref, onMounted, watch, h, computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { Plus, Settings2 } from 'lucide-vue-next'

import AppLayout from '@/layouts/AppLayout.vue'
import SettingsLayout from '@/layouts/settings/Layout.vue'
import HeadingSmall from '@/components/HeadingSmall.vue'
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

// ────────────────────────────────────────────────────────────────
// Derive user ID from Ziggy route params or URL; then fetch user
// ────────────────────────────────────────────────────────────────
const page = usePage()

function getIdFromRouteOrPath(): string | null {
  // Prefer Ziggy route params if present
  // @ts-ignore ziggy types not guaranteed
  const ziggyParams = page.props?.ziggy?.route?.params
  if (ziggyParams?.id) return String(ziggyParams.id)

  // Fallback: parse from pathname /settings/users/:id
  // @ts-ignore ziggy location not guaranteed
  const pathname: string | null = page.props?.ziggy?.location
    ? new URL(page.props.ziggy.location).pathname
    : (typeof window !== 'undefined' ? window.location.pathname : null)

  if (!pathname) return null
  const parts = pathname.split('/').filter(Boolean) // ['settings','users','123']
  const usersIdx = parts.findIndex(p => p === 'users')
  if (usersIdx !== -1 && parts[usersIdx + 1]) return parts[usersIdx + 1]
  return null
}

type User = {
  id: number | string
  name: string
  email?: string
  created_at?: string
}

const user = ref<User | null>(null)
const loadingUser = ref(false)
const loadError = ref<unknown>(null)

async function fetchUserById(id: string) {
  loadingUser.value = true
  loadError.value = null
  try {
    const res = await axios.get(`/api/users/${id}`, {
      headers: {
        // Adjust to your API auth scheme
        Authorization: apiKey ? `Bearer ${apiKey}` : undefined,
      },
    })
    user.value = res.data?.data ?? res.data ?? null
  } catch (e) {
    loadError.value = e
    user.value = null
  } finally {
    loadingUser.value = false
  }
}

// Kick off load on mount
onMounted(() => {
  const id = getIdFromRouteOrPath()
  if (id) fetchUserById(id)
})

// ────────────────────────────────────────────────────────────────
// Breadcrumbs: Users › {user.name}
// ────────────────────────────────────────────────────────────────
const breadcrumbs = computed(() => {
  const usersHref = (typeof route === 'function')
    ? route('settings.users.index')
    : '/settings/users'

  const base = [{ title: 'Users', href: usersHref }]
  if (user.value?.name) base.push({ title: user.value.name, href: null as any })
  return base
})

const showBoardControls = ref(false)

const form = ref({
  name: '',
  description: '',
})

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

const columns = [
  {
    accessorKey: 'id',
    header: 'ID',
    cell: ({ row }) => {
      const id = row.original.id
      const href = (typeof route === 'function')
        ? route('settings.users.show', id)
        : `/settings/users/${id}`
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
        : `/settings/users/${id}`
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

function handleSuccess() {}
function handleError(err: any) { console.error(err) }
</script>

<template>
  <Head :title="user?.name ?? 'User'" />
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
      <div v-if="loadingUser" class="text-sm text-muted-foreground">Loading user…</div>
      <div v-else-if="loadError" class="text-sm text-destructive">Failed to load user.</div>
      <div v-else-if="user">
        <HeadingSmall :title="user.name" />
        <!-- Detail content for the user goes here -->
      </div>
    </SettingsLayout>
  </AppLayout>
</template>
