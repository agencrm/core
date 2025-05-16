<script setup lang="ts">

// resources/js/components/DataTable/CellEditableCombobox.vue

import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
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
  modelValue: Array<number>
  labelId: number
  token: string
  endpoint: string
  optionLabel?: string
  optionValue?: string
}>()

const emit = defineEmits(['update:modelValue'])

const selected = ref(props.modelValue.map(String))
const open = ref(false)
const options = ref<any[]>([])
const loading = ref(false)

const labelField = computed(() => props.optionLabel || 'name')
const valueField = computed(() => props.optionValue || 'id')

const selectedOptions = computed(() =>
  options.value.filter(opt => selected.value.includes(String(opt[valueField.value])))
)

watch(() => props.modelValue, val => {
  selected.value = val.map(String)
})

const fetchOptions = async () => {
  try {
    const res = await axios.get(props.endpoint, {
      headers: {
        Authorization: `Bearer ${props.token}`,
      },
    })
    options.value = res.data.data ?? []
  } catch (e) {
    console.error('Failed to fetch options:', e)
  }
}

const save = async (newVal: string[]) => {
  loading.value = true
  try {
    const response = await axios.patch(`/api/labels/${props.labelId}`, {
      label_group_ids: newVal.map(Number),
    }, {
      headers: {
        Authorization: `Bearer ${props.token}`,
      },
    })

    emit('update:modelValue', newVal.map(Number))
  } catch (e) {
    console.error('Failed to save selection:', e)
  } finally {
    loading.value = false
  }
}

const updateSelection = (id: string) => {
  const index = selected.value.indexOf(id)
  if (index > -1) {
    selected.value.splice(index, 1)
  } else {
    selected.value.push(id)
  }
  save(selected.value)
}

onMounted(fetchOptions)
</script>

<template>
  <Popover v-model:open="open">
    <PopoverTrigger as-child>
      <Button variant="outline" class="w-full justify-between">
        <span>
          {{
            selectedOptions.length
              ? selectedOptions.map(o => o[labelField]).join(', ')
              : 'Select group(s)'
          }}
        </span>
        <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-full p-0">
      <Command v-model="selected">
        <CommandInput placeholder="Search groups..." />
        <CommandEmpty>No groups found.</CommandEmpty>
        <CommandList>
          <CommandGroup>
            <CommandItem
              v-for="opt in options"
              :key="opt[valueField]"
              :value="String(opt[valueField])"
              @select="() => updateSelection(String(opt[valueField]))"
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
</template>
