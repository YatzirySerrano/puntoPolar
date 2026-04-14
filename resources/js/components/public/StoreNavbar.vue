<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
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
const search = ref('')

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

const userLabel = computed(() => {
  if (!props.authUser) return 'Iniciar sesión'

  const firstName = props.authUser.name?.split(' ')[0] || 'Mi cuenta'
  return `Hola, ${firstName}`
})

function toggleMobile() {
  mobileOpen.value = !mobileOpen.value
}

function closeMobile() {
  mobileOpen.value = false
}

function submitSearch() {
  router.get(
    '/',
    { buscar: search.value || undefined },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
      onFinish: () => {
        mobileOpen.value = false
      },
    },
  )
}
</script>

<template>
  <header class="sticky top-0 z-50 border-b border-black/5 bg-white/90 backdrop-blur-xl">

    <div class="mx-auto w-full max-w-[1500px] px-4 py-4 md:px-8">
      <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4 lg:gap-8">
          <Link href="/" class="group shrink-0">
            <img
              :src="logo"
              alt="Mr. Lana"
              class="h-12 w-auto object-contain transition duration-300 group-hover:scale-[1.03] md:h-14"
            />
          </Link>

          <nav class="hidden items-center gap-6 lg:flex">
            <Link href="/" class="text-sm font-bold text-neutral-700 transition hover:text-[var(--brand-blue)]">
              Inicio
            </Link>
            <a href="#catalogo-categorias" class="text-sm font-bold text-neutral-700 transition hover:text-[var(--brand-blue)]">
              Categorías
            </a>
            <a href="#productos-destacados" class="text-sm font-bold text-neutral-700 transition hover:text-[var(--brand-blue)]">
              Destacados
            </a>
            <a href="#catalogo-productos" class="text-sm font-bold text-neutral-700 transition hover:text-[var(--brand-blue)]">
              Productos
            </a>
            <a href="#contacto-home" class="text-sm font-bold text-neutral-700 transition hover:text-[var(--brand-blue)]">
              Contacto
            </a>
          </nav>
        </div>

        <div class="hidden min-w-0 flex-1 items-center justify-end gap-3 lg:flex">
          <form
            class="flex w-full max-w-[420px] items-center rounded-full border border-[var(--brand-gray)] bg-white px-3 py-2 shadow-sm transition focus-within:border-[var(--brand-blue)]"
            @submit.prevent="submitSearch"
          >
            <svg viewBox="0 0 24 24" class="h-5 w-5 shrink-0 text-neutral-400 fill-none stroke-current" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" />
            </svg>

            <input
              v-model="search"
              type="text"
              placeholder="Buscar productos, categorías o SKU..."
              class="h-10 w-full bg-transparent px-3 text-sm text-neutral-700 outline-none placeholder:text-neutral-400"
            />

            <button
              type="submit"
              class="inline-flex items-center justify-center rounded-full bg-[#11142C] px-4 py-2 text-xs font-black uppercase tracking-wide text-white transition hover:bg-[var(--brand-green)] hover:text-[#11142C]"
            >
              Buscar
            </button>
          </form>

          <Link
            :href="props.authUser ? dashboardHref : '/register'"
            class="inline-flex h-12 items-center justify-center rounded-full border border-[var(--brand-gray)] bg-white px-5 text-sm font-black text-[#11142C] transition hover:border-[var(--brand-blue)] hover:text-[var(--brand-blue)]"
          >
            {{ props.authUser ? 'Mi cuenta' : 'Crear cuenta' }}
          </Link>

          <Link
            href="/carrito"
            class="relative inline-flex h-12 w-12 items-center justify-center rounded-full border border-[var(--brand-gray)] bg-white text-[#11142C] shadow-sm transition hover:-translate-y-0.5 hover:border-[var(--brand-green)] hover:text-[var(--brand-green)] hover:shadow-lg"
          >
            <svg viewBox="0 0 24 24" class="h-6 w-6 fill-none stroke-current" stroke-width="1.8">
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
            class="relative inline-flex h-11 w-11 items-center justify-center rounded-full border border-[var(--brand-gray)] bg-white text-[#11142C] shadow-sm"
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
            class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-[var(--brand-gray)] bg-white text-[#11142C]"
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
          <form
            class="flex items-center rounded-2xl border border-[var(--brand-gray)] bg-[#f8f8f8] px-3 py-2"
            @submit.prevent="submitSearch"
          >
            <svg viewBox="0 0 24 24" class="h-5 w-5 shrink-0 text-neutral-400 fill-none stroke-current" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" />
            </svg>

            <input
              v-model="search"
              type="text"
              placeholder="Buscar productos..."
              class="h-10 w-full bg-transparent px-3 text-sm text-neutral-700 outline-none placeholder:text-neutral-400"
            />
          </form>

          <div class="mt-4 grid gap-2">
            <Link href="/" class="rounded-2xl px-4 py-3 text-sm font-bold text-neutral-700 transition hover:bg-[var(--brand-blue)]/8 hover:text-[var(--brand-blue)]" @click="closeMobile">
              Inicio
            </Link>
            <a href="#catalogo-categorias" class="rounded-2xl px-4 py-3 text-sm font-bold text-neutral-700 transition hover:bg-[var(--brand-blue)]/8 hover:text-[var(--brand-blue)]" @click="closeMobile">
              Categorías
            </a>
            <a href="#productos-destacados" class="rounded-2xl px-4 py-3 text-sm font-bold text-neutral-700 transition hover:bg-[var(--brand-blue)]/8 hover:text-[var(--brand-blue)]" @click="closeMobile">
              Destacados
            </a>
            <a href="#catalogo-productos" class="rounded-2xl px-4 py-3 text-sm font-bold text-neutral-700 transition hover:bg-[var(--brand-blue)]/8 hover:text-[var(--brand-blue)]" @click="closeMobile">
              Productos
            </a>
            <a href="#contacto-home" class="rounded-2xl px-4 py-3 text-sm font-bold text-neutral-700 transition hover:bg-[var(--brand-blue)]/8 hover:text-[var(--brand-blue)]" @click="closeMobile">
              Contacto
            </a>
          </div>

          <div class="mt-4 grid grid-cols-2 gap-3">
            <Link
              :href="props.authUser ? dashboardHref : '/login'"
              class="inline-flex items-center justify-center rounded-full border border-[var(--brand-gray)] px-4 py-3 text-sm font-black text-[#11142C]"
              @click="closeMobile"
            >
              {{ props.authUser ? 'Mi cuenta' : 'Entrar' }}
            </Link>

            <Link
              :href="props.authUser ? dashboardHref : '/register'"
              class="inline-flex items-center justify-center rounded-full bg-[#11142C] px-4 py-3 text-sm font-black text-white"
              @click="closeMobile"
            >
              {{ props.authUser ? 'Dashboard' : 'Registro' }}
            </Link>
          </div>
        </div>
      </Transition>
    </div>
  </header>
</template>
