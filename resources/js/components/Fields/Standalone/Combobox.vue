<script setup lang="ts">

// resources/js/components/Fields/Standalone/Combobox.vue

import { ref, onMounted, computed, watch } from 'vue'
import axios from 'axios'

// ShadCN Combobox (Command + Popover)
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from '@/components/ui/command'
import {
  Popover,
  PopoverTrigger,
  PopoverContent,
} from '@/components/ui/popover'
import { Button } from '@/components/ui/button'
import { Check, ChevronsUpDown } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const props = defineProps<{
  modelValue: string | number | Array<string | number> | null
  token: string
  endpoint: string
  optionLabel?: string
  optionValue?: string
  placeholder?: string
  multiple?: boolean
}>()

const emit = defineEmits(['update:modelValue'])

const selected = ref<Array<string>>(Array.isArray(props.modelValue)
  ? props.modelValue.map(String)
  : props.modelValue
  ? [String(props.modelValue)]
  : [])

const options = ref<any[]>([])
const open = ref(false)
const loading = ref(false)

const labelField = computed(() => props.optionLabel || 'name')
const valueField = computed(() => props.optionValue || 'id')

const selectedOptions = computed(() => {
  return options.value.filter(opt =>
    selected.value.includes(String(opt[valueField.value]))
  )
})

const fetchOptions = async () => {
  loading.value = true
  try {
    const res = await axios.get(props.endpoint, {
      headers: {
        Authorization: `Bearer ${props.token}`,
      },
    })
    options.value = res.data.data ?? []
  } catch (err) {
    console.error('Error fetching combobox options:', err)
  } finally {
    loading.value = false
  }
}

const updateSelection = (newVal: string) => {
  if (props.multiple) {
    const index = selected.value.indexOf(newVal)
    if (index > -1) {
      selected.value.splice(index, 1)
    } else {
      selected.value.push(newVal)
    }
    emit('update:modelValue', [...selected.value])
  } else {
    selected.value = [newVal]
    emit('update:modelValue', newVal)
    open.value = false
  }
}

watch(() => props.modelValue, val => {
  selected.value = Array.isArray(val)
    ? val.map(String)
    : val
    ? [String(val)]
    : []
})

onMounted(fetchOptions)
</script>

<template>
  <Popover v-model:open="open">
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        role="combobox"
        :aria-expanded="open"
        class="w-full justify-between"
        :disabled="loading"
      >
        {{
          props.multiple
            ? selectedOptions.map(o => o?.[labelField]).join(', ')
            : selectedOptions[0]?.[labelField] || props.placeholder || 'Select an option'
        }}
        <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-full p-0">
      <Command v-model="selected">
        <CommandInput :placeholder="props.placeholder || 'Search...'" />
        <CommandEmpty>No results found.</CommandEmpty>
        <CommandList>
          <CommandGroup>
            <CommandItem
              v-for="opt in options"
              :key="opt[valueField]"
              :value="String(opt[valueField])"
              @select="updateSelection(String(opt[valueField]))"
            >
              <Check
                :class="cn(
                  'mr-2 h-4 w-4',
                  selected.includes(String(opt[valueField])) ? 'opacity-100' : 'opacity-0'
                )"
              />
              {{ opt[labelField] }}
            </CommandItem>
          </CommandGroup>
        </CommandList>
      </Command>
    </PopoverContent>
  </Popover>


<pre class="mt-4 text-xs text-gray-500 bg-gray-100 p-2 rounded overflow-auto">
  {{ JSON.stringify(props, null, 2) }}
</pre>


</template>
