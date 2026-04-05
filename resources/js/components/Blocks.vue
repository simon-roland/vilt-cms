<script setup lang="ts">
import type { Component } from 'vue'
import { inject } from 'vue'

import { Block } from '../types'

defineProps({
  blocks: {
    type: Array as () => Block[],
    required: true,
  },
})

const components = inject<Record<string, Component>>('cmsBlocks', {})

const getComponent = (block: Block) => {
  const componentName = block.type
    .split('-')
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join('')

  return components[componentName]
}
</script>

<template>
  <component
    :is="getComponent(block)"
    v-for="(block, index) in blocks"
    :key="index"
    :block="block.data as any"
  />
</template>
