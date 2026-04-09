<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import PublicLayout from '@/layouts/PublicLayout.vue'
import ProductCard from '@/components/public/ProductCard.vue'
import heroImg from '@/img/hero-tienda.jpeg'
import promoBanner from '@/img/promo-registro.jpeg'
import sartenesTitle from '@/img/sartenes-title.jpeg'
import decoBanner from '@/img/WEBRecurso 1.png'
import decoDots from '@/img/WEBRecurso 2.png'
import decoLeft from '@/img/WEBRecurso 4.png'
import decoRight from '@/img/WEBRecurso 3.png'
import registerBar from '@/img/WEBRecurso 5.png'

interface Categoria {
  id: number
  nombre: string
  slug: string
  imagen?: string | null
}

interface Producto {
  id: number
  nombre: string
  slug: string
  sku: string
  descripcion?: string | null
  precio: number | string
  precio_comparacion?: number | string | null
  stock: number
  imagen_principal?: string | null
  destacado?: boolean
}

defineProps<{
  categorias: Categoria[]
  destacados: Producto[]
  productos: Producto[]
  bannerPrincipal: {
    titulo: string
    subtitulo: string
    boton: string
  }
}>()
</script>

<template>
  <PublicLayout>
    <Head title="Inicio" />

    <section class="relative overflow-hidden">
      <img :src="heroImg" alt="Banner principal" class="h-[300px] w-full object-cover md:h-[460px]" />
      <div class="absolute inset-0 bg-gradient-to-r from-black/20 via-transparent to-black/10" />

      <div class="absolute inset-0 mx-auto flex max-w-7xl items-center px-4 md:px-6">
        <div class="relative max-w-md overflow-hidden rounded-[30px] bg-black/30 p-6 text-white shadow-2xl backdrop-blur-md md:p-8">
          <img :src="decoBanner" alt="" class="pointer-events-none absolute inset-0 h-full w-full object-cover opacity-35" />
          <div class="relative z-10">
            <h1 class="text-3xl font-black leading-tight md:text-5xl">
              {{ bannerPrincipal.titulo }}
            </h1>
            <p class="mt-3 text-sm md:text-lg">
              {{ bannerPrincipal.subtitulo }}
            </p>
            <button
              class="mt-6 rounded-full bg-black px-7 py-3 text-sm font-black uppercase tracking-wide text-white shadow-lg transition duration-300 hover:-translate-y-0.5 hover:scale-[1.02] hover:bg-neutral-900"
            >
              {{ bannerPrincipal.boton }}
            </button>
          </div>
        </div>
      </div>

      <div class="absolute bottom-4 left-1/2 flex -translate-x-1/2 items-center gap-2">
        <span class="h-3 w-3 rounded-full bg-white shadow" />
        <span class="h-3 w-3 rounded-full bg-white/70 shadow" />
        <span class="h-3 w-3 rounded-full bg-white/70 shadow" />
      </div>
    </section>

    <section class="bg-[#f3f3f3]">
      <div class="mx-auto grid max-w-7xl gap-8 px-4 py-14 md:grid-cols-[250px_1fr] md:px-6">
        <aside class="rounded-[30px] bg-transparent p-2">
          <div class="rounded-[30px] bg-transparent p-4">
            <h2 class="mb-7 text-4xl font-black text-neutral-900">Productos</h2>

            <div class="space-y-3">
              <button
                v-for="categoria in categorias"
                :key="categoria.id"
                type="button"
                class="group flex w-full items-center justify-between rounded-2xl bg-white px-4 py-4 text-left font-extrabold text-neutral-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-white hover:shadow-md"
              >
                <span class="transition group-hover:text-[var(--brand-blue)]">{{ categoria.nombre }}</span>
                <span class="h-2.5 w-2.5 rounded-full bg-[var(--brand-green)] shadow-[0_0_10px_rgba(125,208,60,0.4)]" />
              </button>
            </div>
          </div>
        </aside>

        <div class="space-y-10">
          <div class="flex items-center justify-center md:justify-start">
            <img :src="sartenesTitle" alt="Sartenes" class="h-16 w-auto drop-shadow-[0_8px_18px_rgba(48,190,239,0.2)] md:h-20" />
          </div>

          <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <ProductCard
              v-for="producto in productos"
              :key="producto.id"
              :producto="producto"
            />
          </div>
        </div>
      </div>
    </section>

    <section class="bg-[#f3f3f3] pb-8">
      <div class="mx-auto max-w-7xl px-4 md:px-6">
        <div class="relative overflow-hidden rounded-[34px] shadow-[0_14px_40px_rgba(0,0,0,0.08)]">
          <img :src="promoBanner" alt="Promoción de registro" class="h-auto w-full object-cover transition duration-500 hover:scale-[1.01]" />
        </div>
      </div>
    </section>

    <section class="relative bg-[#f3f3f3] px-4 py-16 md:px-6">
      <img :src="decoLeft" alt="" class="pointer-events-none absolute left-0 top-1/2 hidden w-20 -translate-y-1/2 md:block" />
      <img :src="decoRight" alt="" class="pointer-events-none absolute right-0 top-1/2 hidden w-20 -translate-y-1/2 md:block" />

      <div class="mx-auto max-w-7xl">
        <div class="mb-12 text-center">
          <h2 class="text-4xl font-black uppercase tracking-tight text-[var(--brand-green)] drop-shadow-[0_5px_10px_rgba(125,208,60,0.14)] md:text-6xl">
            Productos destacados
          </h2>
        </div>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          <ProductCard
            v-for="producto in destacados"
            :key="producto.id"
            :producto="producto"
          />
        </div>
      </div>
    </section>

    <section class="bg-[#f3f3f3] pb-14">
      <div class="mx-auto max-w-7xl px-4 md:px-6">
        <div class="overflow-hidden rounded-[40px] bg-white shadow-[0_18px_40px_rgba(0,0,0,0.06)]">
          <div class="relative grid gap-6 p-6 md:grid-cols-[1fr_2fr] md:items-center md:p-8">
            <img :src="registerBar" alt="" class="pointer-events-none absolute inset-x-6 top-6 hidden h-16 w-[calc(100%-3rem)] object-cover opacity-15 md:block" />
            <img :src="decoDots" alt="" class="pointer-events-none absolute right-0 top-0 hidden h-full w-full object-cover opacity-10 md:block" />

            <div class="relative z-10">
              <h3 class="text-3xl font-black text-neutral-900">Regístrate</h3>
              <p class="text-neutral-600">Para recibir las mejores ofertas</p>
            </div>

            <div class="relative z-10">
              <div class="flex flex-col gap-3 md:flex-row">
                <input
                  type="email"
                  placeholder="Ingresa tu correo aquí"
                  class="h-14 flex-1 rounded-full border border-[var(--brand-gray)] bg-[#f7f7f7] px-6 outline-none transition focus:border-[var(--brand-blue)] focus:bg-white focus:shadow-[0_0_0_4px_rgba(48,190,239,0.12)]"
                />
                <button
                  type="button"
                  class="h-14 rounded-full bg-gradient-to-r from-[var(--brand-green)] to-[var(--brand-blue)] px-9 font-black text-white shadow-md transition duration-300 hover:-translate-y-0.5 hover:scale-[1.01] hover:shadow-xl"
                >
                  ¡Regístrate!
                </button>
              </div>

              <p class="mt-4 text-xs text-neutral-400">
                Al registrarme, acepto que mis datos sean tratados para fines mercadotécnicos de acuerdo al Aviso de Privacidad.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>
