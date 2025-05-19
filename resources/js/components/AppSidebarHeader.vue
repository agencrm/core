<script setup lang="ts">
// resources/js/components/AppSidebarHeader.vue

import Breadcrumbs from '@/components/Breadcrumbs.vue'
import { SidebarTrigger } from '@/components/ui/sidebar'
import type { BreadcrumbItemType } from '@/types'

const props = withDefaults(defineProps<{
  breadcrumbs?: BreadcrumbItemType[]
}>(), {
  breadcrumbs: () => [],
})
</script>

<template>
  <header
    class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-sidebar-border/70 bg-background px-6"
  >
    <div class="flex items-center gap-2">
      <SidebarTrigger />
      <template v-if="props.breadcrumbs.length">
        <Breadcrumbs :breadcrumbs="props.breadcrumbs" />
      </template>
    </div>

    <div class="ml-auto flex items-center gap-2">
      <slot name="view-controls" />
    </div>
    <div class="ml-auto flex items-center gap-2">
      <slot name="action-controls" />
    </div>

  </header>
</template>
