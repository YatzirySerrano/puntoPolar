<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import logo from '@/img/logo.png'

const page = usePage()

const carritoCantidad = computed(() => Number(page.props.carrito?.cantidad_items ?? 0))
const flashSuccess = computed(() => page.props.flash?.success)
const flashError = computed(() => page.props.flash?.error)

const mostrarFlash = () => {
  if (flashSuccess.value) {
    Swal.fire({
      icon: 'success',
      title: 'Éxito',
      text: String(flashSuccess.value),
      confirmButtonColor: '#7dd03c',
    })
  }

  if (flashError.value) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: String(flashError.value),
      confirmButtonColor: '#30beef',
    })
  }
}

onMounted(mostrarFlash)
watch(() => page.props.flash, mostrarFlash, { deep: true })
</script>

<template>
  <div class="min-h-screen bg-white text-[var(--brand-text)]">
    <Head title="Mr. Lana" />

    <header class="sticky top-0 z-40 border-b border-[var(--brand-gray)]/70 bg-white/90 backdrop-blur-md">
      <div class="border-b border-[var(--brand-gray)]/40">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-neutral-500 md:px-6">
          <div class="flex items-center gap-5">
            <Link href="/" class="transition hover:text-[var(--brand-blue)]">Inicio</Link>
            <a href="#contacto" class="transition hover:text-[var(--brand-blue)]">Contacto</a>
          </div>

          <div class="flex items-center gap-5">
            <Link href="/carrito" class="transition hover:text-[var(--brand-blue)]">Carrito</Link>
            <Link href="/login" class="transition hover:text-[var(--brand-blue)]">Mi cuenta</Link>
          </div>
        </div>
      </div>

      <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-5 md:flex-row md:items-center md:justify-between md:px-6">
        <Link href="/" class="group shrink-0">
          <img
            :src="logo"
            alt="Mr. Lana"
            class="h-14 w-auto object-contain transition duration-300 group-hover:scale-[1.03]"
          />
        </Link>

        <div class="flex w-full max-w-4xl items-center gap-3">
          <div class="grid flex-1 grid-cols-1 gap-3 md:grid-cols-[1fr_210px_52px]">
            <input
              type="text"
              placeholder="Buscar productos"
              class="h-12 rounded-full border border-[var(--brand-gray)] bg-white px-5 text-sm outline-none transition focus:border-[var(--brand-blue)] focus:shadow-[0_0_0_4px_rgba(48,190,239,0.12)]"
            />

            <select
              class="h-12 rounded-full border border-[var(--brand-gray)] bg-white px-4 text-sm outline-none transition focus:border-[var(--brand-blue)] focus:shadow-[0_0_0_4px_rgba(48,190,239,0.12)]"
            >
              <option value="">Todas las categorías</option>
            </select>

            <button
              type="button"
              class="flex h-12 items-center justify-center rounded-full bg-[var(--brand-blue)] text-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-lg"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m0 0A7.5 7.5 0 1 0 6 6a7.5 7.5 0 0 0 10.65 10.65Z" />
              </svg>
            </button>
          </div>

          <div class="hidden items-center gap-4 pl-2 md:flex">
            <button
              type="button"
              class="text-sm font-medium text-neutral-400 transition hover:text-[var(--brand-blue)]"
            >
              Wishlist (3)
            </button>

            <Link
              href="/carrito"
              class="relative flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-[var(--brand-gray)] bg-white text-[var(--brand-green)] shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-[var(--brand-green)] hover:shadow-lg"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 3h1.386c.51 0 .955.343 1.087.836L5.55 7.5m0 0h12.9c.75 0 1.401.521 1.57 1.252l.798 3.454a1.125 1.125 0 0 1-1.096 1.379H7.125m-1.575-6 1.5 6m0 0a2.25 2.25 0 1 0 4.5 0m-4.5 0h4.5m6 0a2.25 2.25 0 1 0 4.5 0" />
              </svg>

              <span
                v-if="carritoCantidad > 0"
                class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-[var(--brand-green)] px-1 text-[10px] font-bold text-white shadow"
              >
                {{ carritoCantidad }}
              </span>
            </Link>
          </div>
        </div>
      </div>
    </header>

    <main>
      <slot />
    </main>

    <footer id="contacto" class="relative mt-20 overflow-hidden bg-[#efefef]">
      <div class="absolute -left-10 bottom-0 h-52 w-52 rounded-tr-[120px] bg-gradient-to-br from-[var(--brand-green)] to-[var(--brand-blue)] opacity-90" />
      <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 md:grid-cols-3 md:px-6">
        <div class="relative z-10">
          <img :src="logo" alt="Mr. Lana" class="h-16 w-auto" />
        </div>

        <div class="relative z-10 space-y-2 text-sm text-neutral-600">
          <h3 class="text-base font-black text-neutral-900">Contáctanos</h3>
          <p>Atención a clientes</p>
          <p>+52 (777) 379 1464</p>
          <p>Suc. Huamantla</p>
          <p>+52 (247) 472 2975</p>
          <p>Servicio al cliente:</p>
          <p>LUN-SAB: 10:00 am a 8:00 pm</p>
          <p>DOM: 10:00 am a 6:00 pm</p>
          <p>atencion@mr-lana.com</p>
        </div>

        <div class="relative z-10 space-y-2 text-sm text-neutral-600">
          <h3 class="text-base font-black text-neutral-900">Síguenos en</h3>
          <p class="transition hover:text-[var(--brand-blue)]">Facebook</p>
          <p class="transition hover:text-[var(--brand-blue)]">Instagram</p>
          <div class="pt-4">
            <p class="transition hover:text-[var(--brand-blue)]">Términos y Condiciones</p>
            <p class="transition hover:text-[var(--brand-blue)]">Política de Privacidad</p>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>
