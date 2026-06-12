<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import LinkItem from './LinkItem.vue'
import type { Dropdown, NavigationItem } from '../types'

const props = defineProps({
  item: {
    type: Object as () => Dropdown,
    required: true,
  },
  activeSlug: {
    type: [String, null],
    default: null,
  },
  // 'dropdown' floats a panel below the trigger (desktop header);
  // 'disclosure' expands inline (mobile menus, footers, nested groups).
  variant: {
    type: String as () => 'dropdown' | 'disclosure',
    default: 'dropdown',
  },
})

const open = ref(false)
const root = ref<HTMLElement | null>(null)

// A group is highlighted when any descendant link points to the current page.
const containsActive = computed(() => hasActive(props.item.items))

function hasActive(items: NavigationItem[]): boolean {
  return items.some(child => child.type === 'dropdown'
    ? hasActive(child.data.items)
    : child.data.page?.slug === props.activeSlug)
}

function isActive(child: NavigationItem): boolean {
  return child.type === 'link' && child.data.page?.slug === props.activeSlug
}

function onDocumentClick(event: MouseEvent) {
  if (open.value && root.value && !root.value.contains(event.target as Node)) {
    open.value = false
  }
}

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    open.value = false
  }
}

let offNavigate: (() => void) | undefined

onMounted(() => {
  if (props.variant !== 'dropdown') {
    return
  }

  document.addEventListener('click', onDocumentClick)
  document.addEventListener('keydown', onKeydown)
  offNavigate = router.on('navigate', () => (open.value = false))
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
  document.removeEventListener('keydown', onKeydown)
  offNavigate?.()
})
</script>

<template>
  <div ref="root" :class="variant === 'dropdown' ? 'relative' : ''">
    <button
      type="button"
      class="flex items-center gap-1"
      :class="{ 'font-medium': containsActive }"
      :aria-expanded="open"
      aria-haspopup="true"
      @click="open = !open"
    >
      {{ item.label }}
      <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        :class="['size-4 transition-transform', { 'rotate-180': open }]"
      >
        <polyline points="6 9 12 15 18 9" />
      </svg>
    </button>

    <div
      v-if="variant === 'dropdown'"
      v-show="open"
      class="absolute top-full left-0 z-10 mt-2 grid min-w-44 gap-1 rounded-md border border-border bg-background p-2 shadow-md"
    >
      <template v-for="child in item.items" :key="child.data.id">
        <LinkItem
          v-if="child.type === 'link'"
          :item="child.data"
          :active="isActive(child)"
          class="rounded px-2 py-1 hover:bg-secondary transition-colors duration-75"
        />
        <NavigationDropdown
          v-else
          :item="child.data"
          :active-slug="activeSlug"
          variant="disclosure"
          class="px-2 py-1"
        />
      </template>
    </div>

    <div v-else :class="['accordion', { 'accordion--expanded': open }]">
      <div class="overflow-hidden">
        <div class="grid gap-2 pt-2 ml-1 pl-3 border-l border-border">
          <template v-for="child in item.items" :key="child.data.id">
            <LinkItem
              v-if="child.type === 'link'"
              :item="child.data"
              :active="isActive(child)"
            />
            <NavigationDropdown
              v-else
              :item="child.data"
              :active-slug="activeSlug"
              variant="disclosure"
            />
          </template>
        </div>
      </div>
    </div>
  </div>
</template>
