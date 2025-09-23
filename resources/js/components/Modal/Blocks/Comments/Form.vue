<script setup lang="ts">
import { ref, computed, onBeforeUnmount } from 'vue'
import { EditorContent, useEditor } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Placeholder from '@tiptap/extension-placeholder'
import Link from '@tiptap/extension-link'
import HardBreak from '@tiptap/extension-hard-break'
import TextAlign from '@tiptap/extension-text-align'
import Highlight from '@tiptap/extension-highlight'
import TaskList from '@tiptap/extension-task-list'
import TaskItem from '@tiptap/extension-task-item'
import { Table } from '@tiptap/extension-table'
import { TableRow } from '@tiptap/extension-table-row'
import { TableHeader } from '@tiptap/extension-table-header'
import { TableCell } from '@tiptap/extension-table-cell'

import { Button } from '@/components/ui/button'
import { toast } from 'vue-sonner'
import RichTextToolbar from '@/components/Modal/Blocks/Comments/FormToolbar.vue'

type Meta = Record<string, any>

const props = defineProps<{
  commentableType: string
  commentableId: number | string
  parentId?: number
  token?: string
  endpoint?: string
  meta?: Meta
  disabled?: boolean
}>()

const emit = defineEmits<{
  (e: 'created', payload: any): void
  (e: 'created-optimistic', payload: any): void
  (e: 'error', error: any): void
}>()

const isSubmitting = ref(false)
const endpoint = computed(() => props.endpoint ?? '/api/comments')

const editor = useEditor({
  extensions: [
    StarterKit.configure({
      heading: { levels: [1, 2, 3, 4, 5, 6] },
      codeBlock: false,
    }),
    Link.configure({
      openOnClick: true,
      autolink: true,
      linkOnPaste: true,
      HTMLAttributes: { rel: 'noopener noreferrer nofollow', target: '_blank' },
    }),
    Placeholder.configure({ placeholder: 'Write a comment…' }),
    HardBreak.configure({ keepMarks: true }),
    TextAlign.configure({ types: ['heading', 'paragraph'] }),
    Highlight.configure({ multicolor: true }),
    TaskList,
    TaskItem.configure({ nested: true }),
    Table.configure({ resizable: false }),
    TableRow,
    TableHeader,
    TableCell,
  ],
  content: '',
  autofocus: false,
  injectCSS: true,
  editorProps: {
    attributes: {
      class:
        'prose prose-sm max-w-none focus:outline-none p-3 rounded-md border bg-background text-foreground',
    },
  },
})

function ed() {
  return (editor as any)?.value ?? null
}

onBeforeUnmount(() => {
  ed()?.destroy()
})

function getHtml(): string {
  return (ed()?.getHTML() ?? '').trim()
}

function getPlainText(): string {
  const div = document.createElement('div')
  div.innerHTML = getHtml()
  return div.textContent?.replace(/\u200B/g, '').trim() ?? ''
}

function reset() {
  ed()?.commands.clearContent(true)
}

async function submit() {
  if (props.disabled || isSubmitting.value) return

  const html = getHtml()
  const plain = (() => {
    const d = document.createElement('div')
    d.innerHTML = html
    return d.textContent?.replace(/\u200B/g, '').trim() ?? ''
  })()

  if (!plain.length) {
    toast.error('Please enter a comment before saving.')
    return
  }

  // --- OPTIMISTIC: emit a temp row immediately ---
  const tempId = `temp-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`
  const nowIso = new Date().toISOString()
  const optimisticRow = {
    id: tempId,
    commentable_type: props.commentableType,
    commentable_id: Number(props.commentableId),
    parent_id: props.parentId ?? null,
    body: html,
    meta: props.meta ?? null,
    created_at: nowIso,
    updated_at: nowIso,
    __optimistic: true,
  }
  emit('created-optimistic', optimisticRow)

  // --- Real request ---
  const payload = {
    commentable_type: props.commentableType,
    commentable_id: Number(props.commentableId),
    parent_id: props.parentId ?? null,
    body: html,
    // pass temp id along; if your API echoes meta back, you can reconcile later
    meta: { ...(props.meta ?? {}), client_id: tempId },
  }

  isSubmitting.value = true
  try {
    const res = await fetch(endpoint.value, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(props.token ? { Authorization: `Bearer ${props.token}` } : {}),
      },
      body: JSON.stringify(payload),
    })

    const json = await res.json().catch(() => ({}))
    if (!res.ok) throw json

    toast.success('Comment posted')
    // include temp id so the parent can reconcile if it wants
    emit('created', { ...json, __temp_id: tempId })
    reset()
  } catch (err) {
    emit('error', err)
    const msg =
      (err as any)?.message ||
      (typeof (err as any)?.errors === 'object'
        ? Object.values((err as any).errors)[0]?.[0]
        : null) ||
      'Failed to post comment'
    toast.error(String(msg))
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="space-y-2">
    <RichTextToolbar :editor="editor" :disabled="disabled" />
<!-- vertical-only resize, min height 10rem; editor fills the space -->
<div class="rounded-md border resize-y overflow-auto min-h-[10rem] h-[10rem]">
  <EditorContent :editor="editor" class="h-full w-full block" />
</div>

    <div class="flex items-center gap-2">
      <div class="ml-auto flex items-center gap-2 py-2">
        <Button type="button" variant="ghost" size="sm"
                :disabled="!editor || disabled"
                @click="() => ed()?.commands.clearContent(true)"
                title="Clear">
          Clear
        </Button>
        <Button type="button" size="sm"
                :disabled="disabled || isSubmitting || !editor"
                @click="submit">
          <span v-if="isSubmitting">Posting…</span>
          <span v-else>Post</span>
        </Button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.prose :deep(h1) { font-size: 1.25rem; font-weight: 700; }
.prose :deep(h2) { font-size: 1.125rem; font-weight: 700; }
.prose :deep(p)  { margin: 0.25rem 0; }
.prose :deep(ul) { list-style: disc; padding-left: 1.25rem; margin: 0.25rem 0; }
.prose :deep(ol) { list-style: decimal; padding-left: 1.25rem; margin: 0.25rem 0; }
.prose :deep(blockquote) { border-left: 3px solid var(--border); padding-left: .75rem; color: var(--muted-foreground); }


/* Make the ProseMirror root fill the resizable wrapper */
:deep(.ProseMirror) {
  height: 100%;
  min-height: 100%;
  box-sizing: border-box;
}
</style>
