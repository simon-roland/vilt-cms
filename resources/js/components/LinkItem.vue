<script setup lang="ts">
import { useHref } from '../composables/href'
import type { Link } from '../types'

defineProps({
  item: {
    type: Object as () => Link,
    required: true,
  },
  active: {
    type: Boolean,
    default: false,
  },
})
</script>

<template>
  <component
    :is="item.link_type === 'page' ? 'Link' : 'a'"
    :class="[{ 'font-medium': active }]"
    :href="useHref(item)"
    :target="item.target"
  >
    {{ item.label }}
  </component>
</template>
