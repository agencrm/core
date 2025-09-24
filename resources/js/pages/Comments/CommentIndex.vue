<script setup lang="ts">
import { ref, onMounted, watch, h } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import DataTable from '@/components/DataTable/DataTable.vue'
import { Settings2 } from 'lucide-vue-next'
import DOMPurify from 'dompurify'
import Modal from '@/components/Modal/Modal.vue'
import { route } from 'ziggy-js'

const apiKey = import.meta.env.VITE_APP_API_KEY
const CONTACT_FQCN = 'App\\\\Models\\\\Contact'

const breadcrumbs = [{ title: 'Comments', href: '/comments' }]
const showBoardControls = ref(false)

const routeKey = `viewMode:/comments`
const view = ref<'table' | 'board'>('table')
onMounted(() => { try { const s = localStorage.getItem(routeKey); if (s === 'board' || s === 'table') view.value = s } catch {} })
watch(view, v => { try { localStorage.setItem(routeKey, v) } catch {} })

// 👇 this will hold sideloaded "parents" map from the API (if present)
const parentsMap = ref<Record<string, { label: string, data?: any }>>({})

// ⭐ if your DataTable emits the whole JSON payload after fetch, capture the sideloads.
//   (Rename the event if your component uses a different one, e.g. @loaded, @fetched, etc.)
function handleLoadedPayload(payload: any) {
  parentsMap.value = payload?.included?.parents ?? {}
}

// helper: build a key and resolve label/data from sideload OR inline row
function resolveParent(row: any) {
  const t = row.original?.commentable?.type ?? row.original?.commentable_type
  const id = row.original?.commentable?.id ?? row.original?.commentable_id
  const key = t && id ? `${t}:${id}` : null

  // 1) try sideloaded map
  if (key && parentsMap.value[key]) {
    return { type: t, id, label: parentsMap.value[key].label, data: parentsMap.value[key].data }
  }
  // 2) fallback to inline (if your resource injects it)
  const inline = row.original?.commentable
  if (inline?.label) {
    return { type: t, id, label: inline.label, data: inline.data }
  }
  // 3) last resort generic
  return { type: t, id, label: t && id ? `${t.split('\\').pop()} #${id}` : '—', data: null }
}

// helper: choose modal blocks based on type
function resolveBlocksForType(type: string | null, id: number | string | null) {
  if (!type || !id) return []

  // Contact
  if (type === CONTACT_FQCN) {
    return [
      { key: 'fields' },
      {
        key: 'notes',
        props: { notableType: CONTACT_FQCN, notableId: id, token: apiKey },
      },
      {
        key: 'comments',
        props: { commentableType: CONTACT_FQCN, commentableId: id, token: apiKey },
      },
    ]
  }

  // add other commentables here (Entity/Project/Task/etc)
  // if (type === 'App\\Models\\Entity') { ... }

  return [{ key: 'fields' }]
}

const columns = [
  { accessorKey: 'id', header: 'ID', cell: ({ row }: any) => row.getValue('id') },

  // 🔹 Commentable column with Modal trigger
  {
    accessorKey: 'commentable',
    header: 'On',
    cell: ({ row }: any) => {
      const parent = resolveParent(row)
      const id = parent.id
      const type = parent.type
      const label = parent.label ?? '—'

      // pick a canonical show route when it's a Contact
      const href =
        type === CONTACT_FQCN
          ? (typeof route === 'function' ? route('contacts.show', id) : `/contacts/${id}`)
          : '#'

      return h(
        Modal,
        {
          id,
          href,
          title: `${label}${id ? ` (#${id})` : ''}`,
          subtitle: 'Open the full page or close.',
          contentClass: 'w-[32rem] max-w-[95vw]',
          storageKey: 'ui.modal.comments.parentCell',
          showFooter: false,
          blocks: resolveBlocksForType(String(type ?? ''), id),
        },
        {
          trigger: () => label,
        }
      )
    },
    meta: { focusable: true },
  },

  {
    accessorKey: 'body',
    header: 'Body',
    cell: ({ row }: any) => {
      const raw = row.getValue('body') ?? ''
      const clean = DOMPurify.sanitize(String(raw), {
        ALLOWED_TAGS: ['b','i','strong','em','a','p','ul','ol','li','br','pre','code','blockquote','span'],
        ALLOWED_ATTR: ['href','target','rel','class']
      })
      return h('div', { class: 'prose prose-sm max-w-none whitespace-pre-wrap break-words', innerHTML: clean })
    }
  },
  {
    accessorKey: 'updated_at',
    header: 'Updated',
    cell: ({ row }: any) => new Date(row.getValue('updated_at')).toLocaleString(),
  },
]
</script>


<template>
  <Head title="Comments" />
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
      <!-- <Dialog>
        <DialogTrigger>
          <Plus />
        </DialogTrigger>
        <DialogContent>
          <DialogHeader>
            <DialogTitle class="flex items-center justify-between border-b pb-3">
              Create A Comment
            </DialogTitle>
            <DialogDescription>
              <CreateElementForm
                :endpoint="'/api/comments'"
                :fields="form"
                :field-map="fieldMap"
                :token="apiKey"
                :onSuccess="handleSuccess"
                :onError="handleError"
              />
            </DialogDescription>
          </DialogHeader>
        </DialogContent>
      </Dialog> -->
    </template>

    <div class="flex flex-col gap-4 p-4">
      <template v-if="view === 'table'">
        <DataTable
          endpoint-route="api.comments.index"
          :columns="columns"
          search-placeholder="Search comments..."
          :auth-token="apiKey"
          v-slot:expand="{ row }"
        >
          <div class="p-2">
            <strong>Description:</strong> {{ row.original.description || '—' }}
          </div>
        </DataTable>
      </template>

      <template v-else>
        <div class="p-6 text-sm text-muted-foreground border rounded">
          Board view for comments not implemented yet.
        </div>
      </template>
    </div>
  </AppLayout>
</template>
