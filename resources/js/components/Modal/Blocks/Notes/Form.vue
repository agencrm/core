<script setup lang="ts">
// resources/js/components/Modal/Blocks/Comments/Form.vue

import { ref, computed, onBeforeUnmount } from 'vue'
import { EditorContent, useEditor } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Placeholder from '@tiptap/extension-placeholder'
import Link from '@tiptap/extension-link'
import HardBreak from '@tiptap/extension-hard-break'
import { Button } from '@/components/ui/button'
import { toast } from 'vue-sonner'

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
    (e: 'error', error: any): void
}>()

const isSubmitting = ref(false)
const endpoint = computed(() => props.endpoint ?? '/api/comments')

// TIP: useEditor returns a Ref<Editor | null> in your setup
const editor = useEditor({
    extensions: [
        StarterKit.configure({
            heading: { levels: [1, 2, 3] },
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
    // normalize access to the actual Editor instance
    // editor is a Ref<Editor|null>
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

    const plain = getPlainText()
    if (!plain.length) {
        toast.error('Please enter a comment before saving.')
        return
    }

    const payload = {
        commentable_type: props.commentableType,
        commentable_id: Number(props.commentableId),
        parent_id: props.parentId ?? null,
        body: getHtml(),
        meta: props.meta ?? null,
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
        emit('created', json)
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

// Toolbar actions
function cmd(run: (e: any) => void) {
    const e = ed()
    if (!e) return
    run(e)
    e.chain().focus().run()
}
</script>

<template>
    <div class="space-y-2">
        <div class="flex flex-wrap items-center gap-1">
            <Button type="button" variant="secondary" size="sm"
                    :disabled="!editor || disabled"
                    :class="{ 'bg-accent': ed()?.isActive('bold') }"
                    @click="cmd(e => e.chain().toggleBold().run())">
                Bold
            </Button>
            <Button type="button" variant="secondary" size="sm"
                    :disabled="!editor || disabled"
                    :class="{ 'bg-accent': ed()?.isActive('italic') }"
                    @click="cmd(e => e.chain().toggleItalic().run())">
                Italic
            </Button>
            <Button type="button" variant="secondary" size="sm"
                    :disabled="!editor || disabled"
                    :class="{ 'bg-accent': ed()?.isActive('bulletList') }"
                    @click="cmd(e => e.chain().toggleBulletList().run())">
                • List
            </Button>
            <Button type="button" variant="secondary" size="sm"
                    :disabled="!editor || disabled"
                    :class="{ 'bg-accent': ed()?.isActive('orderedList') }"
                    @click="cmd(e => e.chain().toggleOrderedList().run())">
                1. List
            </Button>
            <Button type="button" variant="secondary" size="sm"
                    :disabled="!editor || disabled"
                    :class="{ 'bg-accent': ed()?.isActive('blockquote') }"
                    @click="cmd(e => e.chain().toggleBlockquote().run())">
                Quote
            </Button>
            <Button type="button" variant="secondary" size="sm"
                    :disabled="!editor || disabled"
                    @click="cmd(e => e.chain().setHardBreak().run())">
                New line
            </Button>
            <div class="ml-auto flex items-center gap-2">
                <Button type="button" variant="ghost" size="sm"
                        :disabled="!editor || disabled"
                        @click="reset" title="Clear">
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

        <!-- Vue templates unwrap refs, so passing editor (a ref) is OK -->
        <EditorContent :editor="editor" class="rounded-md border" />
    </div>
</template>

<style scoped>
.prose :deep(h1) { font-size: 1.25rem; font-weight: 700; }
.prose :deep(h2) { font-size: 1.125rem; font-weight: 700; }
.prose :deep(p) { margin: 0.25rem 0; }
.prose :deep(ul) { list-style: disc; padding-left: 1.25rem; margin: 0.25rem 0; }
.prose :deep(ol) { list-style: decimal; padding-left: 1.25rem; margin: 0.25rem 0; }
.prose :deep(blockquote) { border-left: 3px solid var(--border); padding-left: .75rem; color: var(--muted-foreground); }
</style>
