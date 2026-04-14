<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import { computed, onMounted } from 'vue'
import StoreFooter from '@/components/public/StoreFooter.vue'
import StoreNavbar from '@/components/public/StoreNavbar.vue'
import WhaticketWidget from '@/components/integrations/WhaticketWidget.vue'

interface AuthUser {
  id: number
  name: string
  rol?: string
}

interface SharedPageProps extends Record<string, unknown> {
  carrito?: {
    cantidad_items?: number
  }
  flash?: {
    success?: string | null
    error?: string | null
  }
  auth?: {
    user?: AuthUser | null
  }
}

const page = usePage<SharedPageProps>()

const carritoCantidad = computed(() => Number(page.props.carrito?.cantidad_items ?? 0))
const authUser = computed<AuthUser | null>(() => page.props.auth?.user ?? null)
const flashSuccess = computed(() => page.props.flash?.success ?? null)
const flashError = computed(() => page.props.flash?.error ?? null)

onMounted(() => {
  if (flashSuccess.value) {
    Swal.fire({
      icon: 'success',
      title: '¡Todo salió bien!',
      text: String(flashSuccess.value),
      confirmButtonColor: '#7dd03c',
      background: '#ffffff',
      color: '#111827',
      customClass: {
        popup: 'rounded-[22px] shadow-xl',
        confirmButton: 'rounded-xl font-semibold',
      },
    })
    return
  }

  if (flashError.value) {
    Swal.fire({
      icon: 'error',
      title: 'Ups, hubo un problema',
      text: String(flashError.value),
      confirmButtonColor: '#30beef',
      background: '#ffffff',
      color: '#111827',
      customClass: {
        popup: 'rounded-[22px] shadow-xl',
        confirmButton: 'rounded-xl font-semibold',
      },
    })
  }
})
</script>

<template>
  <div class="min-h-screen bg-white text-[var(--brand-text)]">
    <Head title="Mr. Lana" />

    <!-- SOLO el widget real -->
    <WhaticketWidget />

    <StoreNavbar
      :carrito-cantidad="carritoCantidad"
      :auth-user="authUser"
    />

    <main class="w-full">
      <slot />
    </main>

    <StoreFooter />
  </div>
</template>
