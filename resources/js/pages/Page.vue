<script setup lang="ts">
import type { Component } from 'vue'
import { inject } from 'vue'

import Wrapper from '../layouts/Wrapper.vue'
import type { CmsPage } from '../types'

defineOptions({
  layout: [Wrapper],
})

const props = defineProps<{
  page: CmsPage
  cmsToolbar?: unknown
}>()

const layouts = inject<Record<string, Component>>('cmsLayouts', {})
</script>

<template>
  <component :is="layouts[page.layout.type]" :page="page" />
</template>
