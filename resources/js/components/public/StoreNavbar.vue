<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import logo from '@/img/logo.png'

const props = defineProps<{
  carritoCantidad: number
  authUser?: {
    id: number
    name: string
    rol?: string
  } | null
}>()

const mobileOpen = ref(false)

const dashboardHref = computed(() => {
  if (!props.authUser) return '/login'

  switch (props.authUser.rol) {
    case 'admin':
      return '/dashboard'
    case 'vendedor':
      return '/dashboard'
    case 'cliente':
      return '/dashboard'
    default:
      return '/dashboard'
  }
})

const accountHref = computed(() => {
  return props.authUser ? dashboardHref.value : '/login'
})

const mobileAccountLabel = computed(() => {
  return props.authUser ? 'Mi cuenta' : 'Iniciar sesión'
})

function toggleMobile() {
  mobileOpen.value = !mobileOpen.value
}

function closeMobile() {
  mobileOpen.value = false
}
</script>

<template>
  <header class="sticky top-0 z-50 border-b border-black/5 bg-white/90 backdrop-blur-xl">
    <div class="mx-auto w-full max-w-[1500px] px-4 py-4 md:px-8">
      <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4 lg:gap-10">
          <Link href="/" class="group shrink-0">
            <img
              :src="logo"
              alt="Mr. Lana"
              class="h-12 w-auto object-contain transition duration-300 group-hover:scale-[1.03] md:h-14"
            />
          </Link>

          <nav class="hidden items-center gap-7 lg:flex">
            <Link
              href="/"
              class="group relative text-sm font-bold text-neutral-700 transition duration-300 hover:text-[var(--brand-blue)]"
            >
              <span>Inicio</span>
              <span class="absolute -bottom-2 left-0 h-[2px] w-0 rounded-full bg-[var(--brand-blue)] transition-all duration-300 group-hover:w-full" />
            </Link>

            <a
              href="/#catalogo-categorias"
              class="group relative text-sm font-bold text-neutral-700 transition duration-300 hover:text-[var(--brand-blue)]"
            >
              <span>Categorías</span>
              <span class="absolute -bottom-2 left-0 h-[2px] w-0 rounded-full bg-[var(--brand-blue)] transition-all duration-300 group-hover:w-full" />
            </a>

            <a
              href="/#productos-destacados"
              class="group relative text-sm font-bold text-neutral-700 transition duration-300 hover:text-[var(--brand-blue)]"
            >
              <span>Destacados</span>
              <span class="absolute -bottom-2 left-0 h-[2px] w-0 rounded-full bg-[var(--brand-blue)] transition-all duration-300 group-hover:w-full" />
            </a>

            <Link
              href="/productos"
              class="group relative text-sm font-bold text-neutral-700 transition duration-300 hover:text-[var(--brand-blue)]"
            >
              <span>Productos</span>
              <span class="absolute -bottom-2 left-0 h-[2px] w-0 rounded-full bg-[var(--brand-blue)] transition-all duration-300 group-hover:w-full" />
            </Link>

            <a
              href="/#contacto-home"
              class="group relative text-sm font-bold text-neutral-700 transition duration-300 hover:text-[var(--brand-blue)]"
            >
              <span>Contacto</span>
              <span class="absolute -bottom-2 left-0 h-[2px] w-0 rounded-full bg-[var(--brand-blue)] transition-all duration-300 group-hover:w-full" />
            </a>
          </nav>
        </div>

        <div class="hidden items-center gap-3 lg:flex">
          <Link
            :href="accountHref"
            class="group inline-flex h-12 w-12 items-center justify-center rounded-full border border-[var(--brand-gray)] bg-white text-[#11142C] shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-[var(--brand-blue)] hover:text-[var(--brand-blue)] hover:shadow-lg"
            :title="props.authUser ? 'Mi cuenta' : 'Iniciar sesión'"
            :aria-label="props.authUser ? 'Mi cuenta' : 'Iniciar sesión'"
          >
            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current transition duration-300" stroke-width="1.9">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 19.125a7.5 7.5 0 0 1 15 0" />
            </svg>
          </Link>

          <Link
            href="/carrito"
            class="group relative inline-flex h-12 w-12 items-center justify-center rounded-full border border-[var(--brand-gray)] bg-white text-[#11142C] shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-[var(--brand-green)] hover:text-[var(--brand-green)] hover:shadow-lg"
            title="Carrito"
            aria-label="Carrito"
          >
            <svg viewBox="0 0 24 24" class="h-6 w-6 fill-none stroke-current transition duration-300" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.836L5.55 7.5m0 0h12.9c.75 0 1.401.521 1.57 1.252l.798 3.454a1.125 1.125 0 0 1-1.096 1.379H7.125m-1.575-6 1.5 6m0 0a2.25 2.25 0 1 0 4.5 0m-4.5 0h4.5m6 0a2.25 2.25 0 1 0 4.5 0" />
            </svg>

            <span
              v-if="carritoCantidad > 0"
              class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-[var(--brand-green)] px-1 text-[10px] font-black text-white shadow"
            >
              {{ carritoCantidad }}
            </span>
          </Link>
        </div>

        <div class="flex items-center gap-3 lg:hidden">
          <Link
            href="/carrito"
            class="relative inline-flex h-11 w-11 items-center justify-center rounded-full border border-[var(--brand-gray)] bg-white text-[#11142C] shadow-sm transition-all duration-300 hover:border-[var(--brand-green)] hover:text-[var(--brand-green)]"
          >
            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.836L5.55 7.5m0 0h12.9c.75 0 1.401.521 1.57 1.252l.798 3.454a1.125 1.125 0 0 1-1.096 1.379H7.125m-1.575-6 1.5 6m0 0a2.25 2.25 0 1 0 4.5 0m-4.5 0h4.5m6 0a2.25 2.25 0 1 0 4.5 0" />
            </svg>

            <span
              v-if="carritoCantidad > 0"
              class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-[var(--brand-green)] px-1 text-[10px] font-black text-white shadow"
            >
              {{ carritoCantidad }}
            </span>
          </Link>

          <button
            type="button"
            class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-[var(--brand-gray)] bg-white text-[#11142C] transition-all duration-300 hover:border-[var(--brand-blue)] hover:text-[var(--brand-blue)]"
            @click="toggleMobile"
          >
            <svg v-if="!mobileOpen" viewBox="0 0 24 24" class="h-6 w-6 fill-none stroke-current" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
            </svg>
            <svg v-else viewBox="0 0 24 24" class="h-6 w-6 fill-none stroke-current" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" />
            </svg>
          </button>
        </div>
      </div>

      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
      >
        <div
          v-if="mobileOpen"
          class="mt-4 overflow-hidden rounded-[28px] border border-[var(--brand-gray)] bg-white p-4 shadow-[0_24px_55px_rgba(17,20,44,0.10)] lg:hidden"
        >
          <div class="grid gap-2">
            <Link href="/" class="rounded-2xl px-4 py-3 text-sm font-bold text-neutral-700 transition hover:bg-[var(--brand-blue)]/8 hover:text-[var(--brand-blue)]" @click="closeMobile">
              Inicio
            </Link>

            <a href="/#catalogo-categorias" class="rounded-2xl px-4 py-3 text-sm font-bold text-neutral-700 transition hover:bg-[var(--brand-blue)]/8 hover:text-[var(--brand-blue)]" @click="closeMobile">
              Categorías
            </a>

            <a href="/#productos-destacados" class="rounded-2xl px-4 py-3 text-sm font-bold text-neutral-700 transition hover:bg-[var(--brand-blue)]/8 hover:text-[var(--brand-blue)]" @click="closeMobile">
              Destacados
            </a>

            <Link href="/productos" class="rounded-2xl px-4 py-3 text-sm font-bold text-neutral-700 transition hover:bg-[var(--brand-blue)]/8 hover:text-[var(--brand-blue)]" @click="closeMobile">
              Productos
            </Link>

            <a href="/#contacto-home" class="rounded-2xl px-4 py-3 text-sm font-bold text-neutral-700 transition hover:bg-[var(--brand-blue)]/8 hover:text-[var(--brand-blue)]" @click="closeMobile">
              Contacto
            </a>
          </div>

          <div class="mt-4">
            <Link
              :href="accountHref"
              class="inline-flex w-full items-center justify-center rounded-full bg-[#11142C] px-4 py-3 text-sm font-black text-white transition-all duration-300 hover:bg-[var(--brand-green)] hover:text-[#11142C]"
              @click="closeMobile"
            >
              {{ mobileAccountLabel }}
            </Link>
          </div>
        </div>
      </Transition>
    </div>
  </header>
</template>
