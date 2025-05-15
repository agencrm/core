<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import { Monitor, Moon, Sun, ChevronDown } from 'lucide-vue-next';

import DropdownMenu from '@/components/ui/dropdown-menu/DropdownMenu.vue';
import DropdownMenuTrigger from '@/components/ui/dropdown-menu/DropdownMenuTrigger.vue';
import DropdownMenuContent from '@/components/ui/dropdown-menu/DropdownMenuContent.vue';
import DropdownMenuItem from '@/components/ui/dropdown-menu/DropdownMenuItem.vue';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
  { value: 'light', Icon: Sun, label: 'Light' },
  { value: 'dark', Icon: Moon, label: 'Dark' },
  { value: 'system', Icon: Monitor, label: 'System' },
] as const;

const getIcon = (value: string) => {
  return tabs.find((t) => t.value === value)?.Icon ?? Monitor;
};
</script>

<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <button
        class="inline-flex items-center gap-1 rounded-md bg-neutral-100 px-3 py-1.5 text-sm font-medium shadow-sm transition-colors hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-200 dark:hover:bg-neutral-700"
      >
        <component :is="getIcon(appearance)" class="h-4 w-4" />
        <span>{{ appearance.charAt(0).toUpperCase() + appearance.slice(1) }}</span>
        <ChevronDown class="ml-1 h-4 w-4 opacity-70" />
      </button>
    </DropdownMenuTrigger>

    <DropdownMenuContent class="w-40">
      <DropdownMenuItem
        v-for="{ value, Icon, label } in tabs"
        :key="value"
        @click="updateAppearance(value)"
        :class="{ 'bg-neutral-200 dark:bg-neutral-700': appearance === value }"
        class="flex items-center gap-2 px-3 py-2 text-sm text-neutral-700 dark:text-neutral-200"
      >
        <component :is="Icon" class="h-4 w-4" />
        {{ label }}
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>


