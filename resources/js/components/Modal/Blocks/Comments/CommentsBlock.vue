<script setup lang="ts">
// resources/js/components/Modal/Blocks/Comments/CommentsBlock.vue

/**
 * NOTE FOR FUTURE ME:
 * This block is rendered inside ModalBlockContent's accordion.
 * It ONLY needs to render the comment form. I'm keeping the original
 * imports/vars you had in this file commented (or unused) so nothing is "lost",
 * but the active logic is focused on comments to avoid runtime issues.
 */

import { ref, computed, onMounted, watch, h } from 'vue'
import CreateCommentForm from '@/components/Modal/Blocks/Comments/Form.vue'
import DataTable from '@/components/DataTable/DataTable.vue'

/**
 * Props
 * - `id` is forwarded automatically by ModalBlockContent (the parent modal's entity id)
 * - The rest can be passed via blocks[].props from the caller; commentableId is optional
 */
const props = defineProps<{
  id: number | string
  commentableType: string                 // e.g. 'App\\Models\\Contact' (escape as 'App\\\\Models\\\\Contact' in JS)
  commentableId?: number | string         // fallback to `id` if omitted
  token?: string
  parentId?: number
  meta?: Record<string, any>
}>()

const emit = defineEmits<{
  (e: 'created', payload: any): void
  (e: 'error', err: any): void
}>()

const apiKey = import.meta.env.VITE_APP_API_KEY

const resolvedCommentableId = computed(() => props.commentableId ?? props.id)

/* ---------- Optimistic UI bits ---------- */
// Assumes your DataTable exposes a prependRow(row) method (like in ContactIndex)
const tableRef = ref<{ prependRow: (row: any) => void } | null>(null)

// Normalize API response to a plain row the table can render
function extractRow(json: any) {
  const r = json?.data ?? json
  return {
    // sensible defaults if API doesn't echo them
    created_at: r.created_at ?? new Date().toISOString(),
    updated_at: r.updated_at ?? new Date().toISOString(),
    ...r,
  }
}

function onCreated(payload: any) {
  emit('created', payload)
  const row = extractRow(payload)

  if (
    row?.id != null &&
    String(row.commentable_type) === String(props.commentableType) &&
    Number(row.commentable_id) === Number(resolvedCommentableId.value)
  ) {
    tableRef.value?.prependRow(row)
  }
}


function onError(err: any) {
  emit('error', err)
}
/* --------------------------------------- */

// (kept from your version)
const breadcrumbs = [{ title: 'Flows', href: '/comments' }]
const showBoardControls = ref(false)

const form = ref({
  name: '',
  description: '',
})

// fields for the create form
const fieldMap = [
  { key: 'name', type: 'text', label: 'Name', placeholder: 'Flow name' },
  { key: 'description', type: 'text', label: 'Description', placeholder: 'Describe the flow' },
]

// view state
const routeKey = `viewMode:/comments`
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

// comments table columns (trimmed per your latest code)
const columns = [
  { accessorKey: 'id', header: 'ID', cell: ({ row }: any) => row.getValue('id') },
  {
    accessorKey: 'updated_at',
    header: 'Updated',
    cell: ({ row }: any) => new Date(row.getValue('updated_at')).toLocaleString(),
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
  <!-- Comment form -->
  <CreateCommentForm
    :commentable-type="commentableType"
    :commentable-id="resolvedCommentableId"
    :parent-id="parentId"
    :token="token || apiKey"
    :meta="meta"
    @created="onCreated"
    @error="onError"
  />

  <!-- Comments table -->
    <DataTable
        ref="tableRef"
        endpoint-route="api.comments.index"
        :columns="columns"
        search-placeholder="Search comments..."
        :auth-token="apiKey"
        :params="{
            commentable_type: commentableType,
            commentable_id: Number(resolvedCommentableId)
        }"
        v-slot:expand="{ row }"
    >
    <div class="p-2">
      <strong>Description:</strong> {{ row.original.description || '—' }}
    </div>
  </DataTable>
</template>
