<script setup lang="ts">
import { ref, computed, onMounted, watch, h } from 'vue'
import DOMPurify from 'dompurify'
import CreateCommentForm from '@/components/Modal/Blocks/Comments/Form.vue'
import DataTable from '@/components/DataTable/DataTable.vue'

const props = defineProps<{
  id: number | string
  commentableType: string
  commentableId?: number | string
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

// Table ref (expects .prependRow(row))
const tableRef = ref<{ prependRow: (row: any) => void } | null>(null)

// Normalize API or optimistic row to a flat row
function extractRow(json: any) {
  const r = json?.data ?? json
  return {
    created_at: r.created_at ?? new Date().toISOString(),
    updated_at: r.updated_at ?? new Date().toISOString(),
    ...r,
  }
}

// OPTIMISTIC handler: add immediately
function onCreatedOptimistic(payload: any) {
  const row = extractRow(payload)
  if (
    row &&
    String(row.commentable_type) === String(props.commentableType) &&
    Number(row.commentable_id) === Number(resolvedCommentableId.value)
  ) {
    tableRef.value?.prependRow(row)
  }
}

// SUCCESS handler: also prepend (optionally reconcile later if your table supports replace/remove)
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

// Local view state you had (kept minimal)
const routeKey = `viewMode:/comments`
const view = ref<'table' | 'board'>('table')
onMounted(() => {
  try { const s = localStorage.getItem(routeKey); if (s === 'board' || s === 'table') view.value = s } catch {}
})
watch(view, v => { try { localStorage.setItem(routeKey, v) } catch {} })

// Columns — widen sanitizer so highlight/table markup survives
const columns = [
  { accessorKey: 'id', header: 'ID', cell: ({ row }: any) => row.getValue('id') },
  {
    accessorKey: 'body',
    header: 'Body',
    cell: ({ row }: any) => {
      const raw = row.getValue('body') ?? ''
      const clean = DOMPurify.sanitize(String(raw), {
        ALLOWED_TAGS: [
          'b','i','strong','em','a','p','ul','ol','li','br','pre','code','blockquote','span','mark',
          'table','thead','tbody','tr','th','td','col','colgroup'
        ],
        ALLOWED_ATTR: ['href','target','rel','class','style','colspan','rowspan'],
        ALLOW_DATA_ATTR: true,
      })
      return h('div', {
        class: 'prose prose-sm max-w-none whitespace-pre-wrap break-words',
        innerHTML: clean,
      })
    },
  },
  {
    accessorKey: 'updated_at',
    header: 'Updated',
    cell: ({ row }: any) => new Date(row.getValue('updated_at')).toLocaleString(),
  },
]
</script>

<template>
  <!-- Comment form -->
  <CreateCommentForm
    :commentable-type="commentableType"
    :commentable-id="resolvedCommentableId"
    :parent-id="parentId"
    :token="token || apiKey"
    :meta="meta"
    @created-optimistic="onCreatedOptimistic"
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
