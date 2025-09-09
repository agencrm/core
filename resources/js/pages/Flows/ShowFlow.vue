<!-- resources/js/pages/Flows/ShowFlow.vue -->
<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'

// Rete v2 (match official example)
import { NodeEditor, GetSchemes, ClassicPreset } from 'rete'
import { AreaPlugin, AreaExtensions } from 'rete-area-plugin'
import { ConnectionPlugin, Presets as ConnectionPresets } from 'rete-connection-plugin'
import { VuePlugin, Presets as VuePresets, VueArea2D } from 'rete-vue-plugin'

// ----- Types -----
type Schemes = GetSchemes<
  ClassicPreset.Node,
  ClassicPreset.Connection<ClassicPreset.Node, ClassicPreset.Node>
>
type AreaExtra = VueArea2D<Schemes>

// ----- Props -----
type Flow = {
  id: number
  name: string
  slug?: string | null
  description?: string | null
  status?: string | null
  graph?: Record<string, any> | null
  created_at?: string
  updated_at?: string
}
const props = defineProps<{ flow: Flow }>()

// ----- Breadcrumbs -----
const breadcrumbs = [
  { title: 'Flows', href: '/flows' },
  { title: props.flow.name || `Flow #${props.flow.id}`, href: `/flows/${props.flow.id}` },
]

// ----- Rete instances -----
const editorEl = ref<HTMLDivElement | null>(null)
let editor: NodeEditor<Schemes> | null = null
let area: AreaPlugin<Schemes, AreaExtra> | null = null
let connection: ConnectionPlugin<Schemes, AreaExtra> | null = null
let render: VuePlugin<Schemes, AreaExtra> | null = null

// a simple shared socket
const socket = new ClassicPreset.Socket('socket')

// ----- Persistence -----
let saveTimer: number | undefined

async function saveGraphDebounced() {
  if (saveTimer) window.clearTimeout(saveTimer)
  saveTimer = window.setTimeout(async () => {
    if (!editor) return
    const json = await editor.toJSON()
    await fetch(`/api/flows/${props.flow.id}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({ graph: json })
    }).catch(() => {})
  }, 500)
}

async function manualSave() {
  if (!editor) return
  const json = await editor.toJSON()
  await fetch(`/api/flows/${props.flow.id}`, {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ graph: json })
  })
}

// ----- Editor lifecycle -----
async function initEditor() {
  if (!editorEl.value) return

  editor = new NodeEditor<Schemes>()
  area = new AreaPlugin<Schemes, AreaExtra>(editorEl.value)
  connection = new ConnectionPlugin<Schemes, AreaExtra>()
  render = new VuePlugin<Schemes, AreaExtra>()

  // Selection helpers & ordering (from example)
  AreaExtensions.selectableNodes(area, AreaExtensions.selector(), {
    accumulating: AreaExtensions.accumulateOnCtrl()
  })
  AreaExtensions.simpleNodesOrder(area)

  // Presets
  render.addPreset(VuePresets.classic.setup())
  connection.addPreset(ConnectionPresets.classic.setup())

  // Wire plugins
  editor.use(area)
  area.use(connection)
  area.use(render)

  // Load existing graph (if any) or start empty
  const initialGraph = props.flow.graph ?? {}
  if (Object.keys(initialGraph).length) {
    await editor.fromJSON(initialGraph as any)
  } else {
    // Empty canvas; user can add nodes with the button
  }

  AreaExtensions.zoomAt(area, editor.getNodes())

  // Auto-save on structural changes
  editor.addPipe(ctx => {
    if (
      ctx.type === 'nodecreated' ||
      ctx.type === 'noderemoved' ||
      ctx.type === 'connectioncreated' ||
      ctx.type === 'connectionremoved'
    ) {
      saveGraphDebounced()
    }
    return ctx
  })
}

function destroyEditor() {
  try { area?.destroy() } catch {}
  editor = null
  area = null
  connection = null
  render = null
}

// ----- Actions -----
async function addNode() {
  if (!editor || !area) return

  const count = editor.getNodes().length + 1
  const node = new ClassicPreset.Node(`Node ${count}`)
  node.addControl('label', new ClassicPreset.InputControl('text', { initial: `hello ${count}` }))
  node.addInput('in', new ClassicPreset.Input(socket, 'In'))
  node.addOutput('out', new ClassicPreset.Output(socket, 'Out'))

  await editor.addNode(node)
  await area.translate(node.id, { x: 240 * (count - 1), y: 0 })
  saveGraphDebounced()
}

function zoomToFit() {
  if (!area || !editor) return
  AreaExtensions.zoomAt(area, editor.getNodes())
}

onMounted(initEditor)
onBeforeUnmount(destroyEditor)
</script>

<template>
  <Head :title="`Flow Builder: ${props.flow.name || props.flow.id}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex items-center justify-between p-4 border-b gap-2">
      <div>
        <h1 class="text-lg font-semibold">Flow Builder</h1>
        <p class="text-xs text-muted-foreground">
          {{ props.flow.name || `Flow #${props.flow.id}` }}
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button class="rounded border px-3 py-1 text-sm" @click="addNode">Add Node</button>
        <button class="rounded border px-3 py-1 text-sm" @click="zoomToFit">Zoom to Fit</button>
        <button class="rounded bg-black text-white px-3 py-1 text-sm" @click="manualSave">Save</button>
      </div>
    </div>

    <div class="p-0">
      <div ref="editorEl" class="w-full" style="height: calc(100vh - 180px);"></div>
    </div>
  </AppLayout>
</template>
