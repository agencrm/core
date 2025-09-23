<script setup lang="ts">
// resources/js/components/Modal/Blocks/Comments/FormToolbar.vue

import { ref, computed } from 'vue'
import type { Editor } from '@tiptap/core'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuTrigger,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu'

// Lucide icons
import {
  Undo2 as IconUndo,
  Redo2 as IconRedo,
  Bold as IconBold,
  Italic as IconItalic,
  Strikethrough as IconStrike,
  Highlighter as IconHighlighter,
  Eraser as IconEraser,
  Minus as IconMinus,
  CornerDownLeft as IconCornerDownLeft,
  Table as IconTable,
  List as IconList,
  Quote as IconQuote,
  Link as IconLink,
  Heading2 as IconH2,
  AlignLeft as IconAlignLeft,
  AlignCenter as IconAlignCenter,
  AlignRight as IconAlignRight,
  AlignJustify as IconAlignJustify,
  ChevronDown as IconChevronDown,
  ChevronUp as IconChevronUp,
} from 'lucide-vue-next'

const props = defineProps<{
  // Parent usually passes Ref<Editor|null> from useEditor; we unwrap it.
  editor: Editor | null | any
  disabled?: boolean
}>()

function ed(): Editor | null {
  const maybeRef = props.editor as any
  return maybeRef && typeof maybeRef === 'object' && 'value' in maybeRef
    ? (maybeRef.value as Editor | null)
    : (props.editor as Editor | null)
}

const ready = computed(() => !!ed() && !props.disabled)

function isActive(nameOrAttrs: any, attrs?: any) {
  const e = ed()
  return e?.isActive(nameOrAttrs as any, attrs) ?? false
}
function run(cb: (e: Editor) => void) {
  const e = ed()
  if (!e || props.disabled) return
  cb(e) // every cb must call chain().focus().…().run()
}

// --- Actions (structure mirrors your reference) ---
const undo = () => run(e => e.chain().focus().undo().run())
const redo = () => run(e => e.chain().focus().redo().run())

const toggleBold = () => run(e => e.chain().focus().toggleBold().run())
const toggleItalic = () => run(e => e.chain().focus().toggleItalic().run())
const toggleStrike = () => run(e => e.chain().focus().toggleStrike().run())
const clearFormatting = () => run(e => e.chain().focus().unsetAllMarks().clearNodes().run())
const insertHorizontalRule = () => run(e => e.chain().focus().setHorizontalRule().run())
const insertHardBreak = () => run(e => e.chain().focus().setHardBreak().run())

const highlightColors = [
  { color: '#FFEA00', label: 'Yellow' },
  { color: '#FF5722', label: 'Orange' },
  { color: '#4CAF50', label: 'Green' },
  { color: '#2196F3', label: 'Blue' },
  { color: '#E91E63', label: 'Pink' },
]
const setHighlight = (color: string) =>
  run(e => e.chain().focus().setMark('highlight', { color }).run())

const alignLeft = () => run(e => e.chain().focus().setTextAlign('left').run())
const alignCenter = () => run(e => e.chain().focus().setTextAlign('center').run())
const alignRight = () => run(e => e.chain().focus().setTextAlign('right').run())
const alignJustify = () => run(e => e.chain().focus().setTextAlign('justify').run())

const toggleHeading = (level: number) => run(e => e.chain().focus().toggleHeading({ level }).run())
const setParagraph = () => run(e => e.chain().focus().setParagraph().run())

const toggleBulletList = () => run(e => e.chain().focus().toggleBulletList().run())
const toggleOrderedList = () => run(e => e.chain().focus().toggleOrderedList().run())
const toggleTaskList = () => run(e => e.chain().focus().toggleTaskList().run())
const sinkListItem = () => run(e => e.chain().focus().sinkListItem('listItem').run())
const liftListItem = () => run(e => e.chain().focus().liftListItem('listItem').run())
const splitListItem = () => run(e => e.chain().focus().splitListItem('listItem').run())

const insertTable = () =>
  run(e => e.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run())
const addColumnBefore = () => run(e => e.chain().focus().addColumnBefore().run())
const addColumnAfter = () => run(e => e.chain().focus().addColumnAfter().run())
const deleteColumn = () => run(e => e.chain().focus().deleteColumn().run())
const addRowBefore = () => run(e => e.chain().focus().addRowBefore().run())
const addRowAfter = () => run(e => e.chain().focus().addRowAfter().run())
const deleteRow = () => run(e => e.chain().focus().deleteRow().run())
const deleteTable = () => run(e => e.chain().focus().deleteTable().run())

function toggleLink() {
  const e = ed()
  if (!e || props.disabled) return
  const existing = e.getAttributes('link')?.href as string | undefined
  const url = window.prompt('Enter URL', existing ?? '')
  if (url === null) return
  if (url.trim() === '') {
    run(e => e.chain().focus().unsetLink().run())
  } else {
    run(e =>
      e
        .chain()
        .focus()
        .setLink({ href: url.trim(), target: '_blank', rel: 'noopener noreferrer nofollow' })
        .run(),
    )
  }
}

// chevrons flip on open
const openHeadings = ref(false)
const openLists = ref(false)
const openTables = ref(false)
const openHighlight = ref(false)
</script>

<template>
  <div class="flex flex-wrap items-center gap-0.5">
    <!-- Undo / Redo -->
    <Button variant="ghost" size="sm" class="p-1 h-7" :disabled="!ready" title="Undo" @click="undo">
      <IconUndo class="h-3.5 w-3.5" />
    </Button>
    <Button variant="ghost" size="sm" class="p-1 h-7" :disabled="!ready" title="Redo" @click="redo">
      <IconRedo class="h-3.5 w-3.5" />
    </Button>

    <span class="mx-0.5 h-5 w-px bg-border/70 inline-block" aria-hidden="true"></span>

    <!-- Bold / Italic / Strike -->
    <Button variant="ghost" size="sm" class="p-1 h-7"
            :disabled="!ready" :class="{ 'bg-accent': isActive('bold') }"
            title="Bold" @click="toggleBold">
      <IconBold class="h-3.5 w-3.5" />
    </Button>
    <Button variant="ghost" size="sm" class="p-1 h-7"
            :disabled="!ready" :class="{ 'bg-accent': isActive('italic') }"
            title="Italic" @click="toggleItalic">
      <IconItalic class="h-3.5 w-3.5" />
    </Button>
    <Button variant="ghost" size="sm" class="p-1 h-7"
            :disabled="!ready" :class="{ 'bg-accent': isActive('strike') }"
            title="Strikethrough" @click="toggleStrike">
      <IconStrike class="h-3.5 w-3.5" />
    </Button>

    <!-- Highlight dropdown -->
    <DropdownMenu v-model:open="openHighlight">
      <DropdownMenuTrigger as-child>
        <Button variant="ghost" size="sm" class="p-1 h-7"
                :disabled="!ready" :class="{ 'bg-accent': isActive('highlight') }"
                title="Highlight">
          <span class="flex items-center gap-0.5">
            <IconHighlighter class="h-3.5 w-3.5" />
            <component :is="openHighlight ? IconChevronUp : IconChevronDown" class="h-3 w-3 opacity-70" />
          </span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="start" class="min-w-[10rem]">
        <DropdownMenuItem v-for="opt in highlightColors" :key="opt.color"
                          @click="setHighlight(opt.color)">
          <span class="inline-block h-3 w-3 rounded mr-2" :style="{ backgroundColor: opt.color }" />
          {{ opt.label }}
        </DropdownMenuItem>
        <DropdownMenuSeparator />
        <DropdownMenuItem @click="clearFormatting">
          Clear formatting
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>

    <span class="mx-0.5 h-5 w-px bg-border/70 inline-block" aria-hidden="true"></span>

    <!-- HR / Hard break -->
    <Button variant="ghost" size="sm" class="p-1 h-7" :disabled="!ready" title="Horizontal rule" @click="insertHorizontalRule">
      <IconMinus class="h-3.5 w-3.5" />
    </Button>
    <Button variant="ghost" size="sm" class="p-1 h-7" :disabled="!ready" title="Hard break" @click="insertHardBreak">
      <IconCornerDownLeft class="h-3.5 w-3.5" />
    </Button>

    <span class="mx-0.5 h-5 w-px bg-border/70 inline-block" aria-hidden="true"></span>

    <!-- Table dropdown -->
    <DropdownMenu v-model:open="openTables">
      <DropdownMenuTrigger as-child>
        <Button variant="ghost" size="sm" class="p-1 h-7" :disabled="!ready" title="Table">
          <span class="flex items-center gap-0.5">
            <IconTable class="h-3.5 w-3.5" />
            <component :is="openTables ? IconChevronUp : IconChevronDown" class="h-3 w-3 opacity-70" />
          </span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="start" class="min-w-[12rem]">
        <DropdownMenuItem @click="insertTable">Insert 3×3</DropdownMenuItem>
        <DropdownMenuSeparator />
        <DropdownMenuItem @click="addColumnBefore">Add column before</DropdownMenuItem>
        <DropdownMenuItem @click="addColumnAfter">Add column after</DropdownMenuItem>
        <DropdownMenuItem @click="deleteColumn">Delete column</DropdownMenuItem>
        <DropdownMenuSeparator />
        <DropdownMenuItem @click="addRowBefore">Add row before</DropdownMenuItem>
        <DropdownMenuItem @click="addRowAfter">Add row after</DropdownMenuItem>
        <DropdownMenuItem @click="deleteRow">Delete row</DropdownMenuItem>
        <DropdownMenuSeparator />
        <DropdownMenuItem @click="deleteTable">Delete table</DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>

    <span class="mx-0.5 h-5 w-px bg-border/70 inline-block" aria-hidden="true"></span>

    <!-- Lists dropdown -->
    <DropdownMenu v-model:open="openLists">
      <DropdownMenuTrigger as-child>
        <Button variant="ghost" size="sm" class="p-1 h-7"
                :disabled="!ready"
                :class="{ 'bg-accent': isActive('bulletList') || isActive('orderedList') }"
                title="Lists">
          <span class="flex items-center gap-0.5">
            <IconList class="h-3.5 w-3.5" />
            <component :is="openLists ? IconChevronUp : IconChevronDown" class="h-3 w-3 opacity-70" />
          </span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="start" class="min-w-[12rem]">
        <DropdownMenuItem @click="toggleBulletList" :class="{ 'bg-accent': isActive('bulletList') }">
          Bullet list
        </DropdownMenuItem>
        <DropdownMenuItem @click="toggleOrderedList" :class="{ 'bg-accent': isActive('orderedList') }">
          Numbered list
        </DropdownMenuItem>
        <DropdownMenuItem @click="toggleTaskList">
          Task list
        </DropdownMenuItem>
        <DropdownMenuSeparator />
        <DropdownMenuItem :disabled="!ed() || !ed()?.can().sinkListItem?.('listItem')" @click="sinkListItem">
          Indent
        </DropdownMenuItem>
        <DropdownMenuItem :disabled="!ed() || !ed()?.can().liftListItem?.('listItem')" @click="liftListItem">
          Outdent
        </DropdownMenuItem>
        <DropdownMenuItem :disabled="!ed() || !ed()?.can().splitListItem?.('listItem')" @click="splitListItem">
          Split item
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>

    <span class="mx-0.5 h-5 w-px bg-border/70 inline-block" aria-hidden="true"></span>

    <!-- Alignment -->
    <Button variant="ghost" size="sm" class="p-1 h-7"
            :disabled="!ready" :class="{ 'bg-accent': isActive({ textAlign: 'left' }) }"
            title="Align left" @click="alignLeft">
      <IconAlignLeft class="h-3.5 w-3.5" />
    </Button>
    <Button variant="ghost" size="sm" class="p-1 h-7"
            :disabled="!ready" :class="{ 'bg-accent': isActive({ textAlign: 'center' }) }"
            title="Align center" @click="alignCenter">
      <IconAlignCenter class="h-3.5 w-3.5" />
    </Button>
    <Button variant="ghost" size="sm" class="p-1 h-7"
            :disabled="!ready" :class="{ 'bg-accent': isActive({ textAlign: 'right' }) }"
            title="Align right" @click="alignRight">
      <IconAlignRight class="h-3.5 w-3.5" />
    </Button>
    <Button variant="ghost" size="sm" class="p-1 h-7"
            :disabled="!ready" :class="{ 'bg-accent': isActive({ textAlign: 'justify' }) }"
            title="Justify" @click="alignJustify">
      <IconAlignJustify class="h-3.5 w-3.5" />
    </Button>

    <span class="mx-0.5 h-5 w-px bg-border/70 inline-block" aria-hidden="true"></span>

    <!-- Headings -->
    <DropdownMenu v-model:open="openHeadings">
      <DropdownMenuTrigger as-child>
        <Button variant="ghost" size="sm" class="p-1 h-7"
                :disabled="!ready"
                :class="{ 'bg-accent': isActive('heading', { level: 1 }) || isActive('heading', { level: 2 }) || isActive('heading', { level: 3 }) }"
                title="Headings">
          <span class="flex items-center gap-0.5">
            <IconH2 class="h-3.5 w-3.5" />
            <component :is="openHeadings ? IconChevronUp : IconChevronDown" class="h-3 w-3 opacity-70" />
          </span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="start" class="min-w-[10rem]">
        <DropdownMenuItem v-for="level in [1,2,3,4,5,6]" :key="level"
                          :class="{ 'bg-accent': isActive('heading', { level }) }"
                          @click="toggleHeading(level)">
          H{{ level }}
        </DropdownMenuItem>
        <DropdownMenuSeparator />
        <DropdownMenuItem :class="{ 'bg-accent': isActive('paragraph') }" @click="setParagraph">
          Paragraph
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>

    <span class="mx-0.5 h-5 w-px bg-border/70 inline-block" aria-hidden="true"></span>

    <!-- Quote / Link -->
    <Button variant="ghost" size="sm" class="p-1 h-7"
            :disabled="!ready" :class="{ 'bg-accent': isActive('blockquote') }"
            title="Blockquote" @click="run(e => e.chain().focus().toggleBlockquote().run())">
      <IconQuote class="h-3.5 w-3.5" />
    </Button>
    <Button variant="ghost" size="sm" class="p-1 h-7"
            :disabled="!ready" :class="{ 'bg-accent': isActive('link') }"
            title="Link" @click="toggleLink">
      <IconLink class="h-3.5 w-3.5" />
    </Button>

    <!-- Clear (redundant to menu, but handy) -->
    <Button variant="ghost" size="sm" class="p-1 h-7" :disabled="!ready" title="Clear formatting" @click="clearFormatting">
      <IconEraser class="h-3.5 w-3.5" />
    </Button>
  </div>
</template>
