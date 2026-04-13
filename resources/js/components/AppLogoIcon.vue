<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue'
import { useSidebar } from '@/components/ui/sidebar'

import logoFull from '@/img/logo.png'
import logoIcon from '@/img/logo.ico'

defineOptions({
  inheritAttrs: false,
})

type Props = {
  className?: HTMLAttributes['class']
}

const props = defineProps<Props>()
const { state } = useSidebar()

const logoSrc = computed(() => {
  return state.value === 'expanded' ? logoFull : logoIcon
})

const logoClass = computed(() => {
  return state.value === 'expanded'
    ? ['h-12 w-50 object-contain', props.className]
    : ['h-12 w-12 object-contain', props.className]
})
</script>

<template>
  <img
    v-bind="$attrs"
    :class="logoClass"
    :src="logoSrc"
    alt="Mr Lana"
  />
</template>
