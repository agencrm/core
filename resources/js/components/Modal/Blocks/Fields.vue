<!-- resources/js/components/Modal/Blocks/Fields.vue -->
<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { route as ziggyRoute } from 'ziggy-js'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'

type FieldMapItem = {
  key: string
  label?: string
  // Optional formatter; if provided, receives (value, fullRow)
  formatter?: (value: any, row: Record<string, any>) => string
}

const props = defineProps<{
  id: number | string
  // Prefer a Ziggy route name like 'api.contacts.show' that accepts {id}
  endpointRoute?: string
  // Or pass a full URL; '{id}' will be replaced, otherwise '/:id' appended
  endpointUrl?: string
  // Bearer token if your API requires it
  token?: string
  // Controls which fields appear and their labels/format
  fieldMap?: FieldMapItem[]
  // Keys to omit when auto-rendering (when fieldMap not provided)
  exclude?: string[]
}>()

const loading = ref(true)
const error = ref<string | null>(null)
const item = ref<Record<string, any> | null>(null)

function resolveUrl(): string {
  if (props.endpointRoute) {
    try {
      return (ziggyRoute as any)(props.endpointRoute, props.id)
    } catch {
      // Fall through to endpointUrl if Ziggy route missing
    }
  }
  const base = props.endpointUrl ?? ''
  if (!base) return `/api/contacts/${props.id}` // sensible default
  if (base.includes('{id}')) return base.replace('{id}', String(props.id))
  if (base.endsWith('/')) return `${base}${props.id}`
  return `${base}/${props.id}`
}

async function fetchItem() {
  loading.value = true
  error.value = null
  try {
    const url = resolveUrl()
    const headers: Record<string, string> = {}
    if (props.token) headers.Authorization = `Bearer ${props.token}`

    const { data } = await axios.get(url, { headers })
    // Support both resource wrapper and raw JSON
    item.value = (data && data.data) ? data.data : data
  } catch (e: any) {
    error.value = e?.message ?? 'Failed to load fields'
  } finally {
    loading.value = false
  }
}

onMounted(fetchItem)

const defaultExclude = computed(() => new Set([ 'id', ...(props.exclude ?? []) ]))

function defaultLabelForKey(k: string): string {
  return k
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (m) => m.toUpperCase())
}

const rows = computed(() => {
  if (!item.value) return []
  const row = item.value

  if (props.fieldMap && props.fieldMap.length) {
    return props.fieldMap.map(({ key, label, formatter }) => {
      const raw = row[key]
      let val: any = raw

      // Basic date prettifying if key looks like created_at/updated_at and no formatter provided
      if (!formatter && /_at$/.test(key) && raw) {
        try { val = new Date(raw).toLocaleString() } catch { val = raw }
      } else if (formatter) {
        try { val = formatter(raw, row) } catch { val = raw }
      }

      return {
        label: label ?? defaultLabelForKey(key),
        value: val ?? '—',
      }
    })
  }

  // Auto: list enumerable keys except excluded
  return Object.keys(row)
    .filter((k) => !defaultExclude.value.has(k))
    .map((k) => {
      let val: any = row[k]
      if (/_at$/.test(k) && val) {
        try { val = new Date(val).toLocaleString() } catch {}
      }
      if (val === null || typeof val === 'undefined' || val === '') val = '—'
      if (typeof val === 'object') {
        try { val = JSON.stringify(val) } catch {}
      }
      return {
        label: defaultLabelForKey(k),
        value: val,
      }
    })
})
</script>

<template>
  <div class="w-full">
    <div v-if="loading" class="text-sm text-muted-foreground">Loading…</div>
    <div v-else-if="error" class="text-sm text-destructive">{{ error }}</div>
    <div v-else>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead class="w-1/3">Field</TableHead>
            <TableHead>Value</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="(r, idx) in rows" :key="idx">
            <TableCell class="font-medium">{{ r.label }}</TableCell>
            <TableCell class="text-muted-foreground">
              {{ r.value }}
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>
  </div>
</template>
