<script setup lang="ts">

import { ref } from 'vue'

const emit = defineEmits(['update:modelValue'])

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: true,
  },
  slotClass: {
    type: String,
    default: '',
  },
})

const open = ref(props.modelValue)

const toggle = () => {
  open.value = !open.value
  emit('update:modelValue', open.value)
}
</script>

<template>
  <div class="grid">
    <button class="flex items-center lg:gap-1 xl:gap-2" @click="toggle">
      <slot name="title" :open="open" />
      <div class="relative trigger-icons ml-auto mr-[-0.5rem]">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          :class="['size-5 transition-transform ease-linear', { 'rotate-90': open }]"
        >
          <polyline points="9 18 15 12 9 6" />
        </svg>
      </div>
    </button>
    <div :class="['accordion', { 'accordion--expanded': open }]">
      <div :class="['px-[1px] overflow-hidden', slotClass]">
        <slot />
      </div>
    </div>
  </div>
</template>
