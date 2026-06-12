<script setup lang="ts">
import LinkItem from './LinkItem.vue'
import NavigationDropdown from './NavigationDropdown.vue'
import type { NavigationItem } from '../types'

defineProps({
  items: {
    type: Array as () => NavigationItem[],
    required: true,
  },
  activeSlug: {
    type: [String, null],
    default: null,
  },
  // 'horizontal' floats dropdown panels below their triggers (desktop header);
  // 'vertical' expands groups inline (mobile menus, footer columns).
  variant: {
    type: String as () => 'horizontal' | 'vertical',
    default: 'horizontal',
  },
})
</script>

<template>
  <template v-for="item in items" :key="item.data.id">
    <LinkItem
      v-if="item.type === 'link'"
      :item="item.data"
      :active="item.data.page?.slug === activeSlug"
    />
    <NavigationDropdown
      v-else-if="item.type === 'dropdown'"
      :item="item.data"
      :active-slug="activeSlug"
      :variant="variant === 'horizontal' ? 'dropdown' : 'disclosure'"
    />
  </template>
</template>
