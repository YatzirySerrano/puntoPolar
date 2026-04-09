<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import PublicLayout from '@/layouts/PublicLayout.vue'
import ProductCard from '@/components/public/ProductCard.vue'
import heroImg from '@/img/hero-tienda.jpeg'
import promoBanner from '@/img/promo-registro.jpeg'
import sartenesTitle from '@/img/sartenes-title.jpeg'

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
      <img :src="heroImg" alt="Banner principal" class="h-[260px] w-full object-cover md:h-[430px]" />
      <div class="absolute inset-0 bg-black/15" />

      <div class="absolute inset-0 mx-auto flex max-w-7xl items-center px-4 md:px-6">
        <div class="max-w-md rounded-[28px] bg-black/35 p-6 text-white shadow-xl backdrop-blur-[2px]">
          <h1 class="text-3xl font-black leading-tight md:text-5xl">
            {{ bannerPrincipal.titulo }}
          </h1>
          <p class="mt-3 text-sm md:text-lg">
            {{ bannerPrincipal.subtitulo }}
          </p>
          <button
            class="mt-5 rounded-full bg-black px-6 py-3 text-sm font-black uppercase tracking-wide text-white transition hover:opacity-90"
          >
            {{ bannerPrincipal.boton }}
          </button>
        </div>
      </div>
    </section>

    <section class="mx-auto grid max-w-7xl gap-8 px-4 py-12 md:grid-cols-[240px_1fr] md:px-6">
      <aside class="rounded-3xl border border-[#d5d5d5] bg-white p-6">
        <h2 class="mb-6 text-3xl font-black text-neutral-900">Productos</h2>

        <div class="space-y-3">
          <button
            v-for="categoria in categorias"
            :key="categoria.id"
            type="button"
            class="flex w-full items-center justify-between rounded-2xl border border-transparent px-4 py-3 text-left font-bold text-neutral-700 transition hover:border-[#30beef] hover:bg-[#effbff] hover:text-[#30beef]"
          >
            <span>{{ categoria.nombre }}</span>
            <span class="h-2 w-2 rounded-full bg-[#7dd03c]" />
          </button>
        </div>
      </aside>

      <div class="space-y-10">
        <div class="flex items-center justify-center md:justify-start">
          <img :src="sartenesTitle" alt="Sartenes" class="h-16 w-auto md:h-20" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <ProductCard
            v-for="producto in productos"
            :key="producto.id"
            :producto="producto"
          />
        </div>
      </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-4 md:px-6">
      <div class="overflow-hidden rounded-[32px]">
        <img :src="promoBanner" alt="Promoción de registro" class="h-auto w-full object-cover" />
      </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-16 md:px-6">
      <div class="mb-10 text-center">
        <h2 class="text-4xl font-black uppercase tracking-tight text-[#7dd03c] md:text-6xl">
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
    </section>

    <section class="mx-auto max-w-7xl px-4 pb-10 md:px-6">
      <div class="rounded-[32px] border border-[#d5d5d5] bg-white p-6 md:p-8">
        <div class="grid gap-6 md:grid-cols-[1fr_2fr] md:items-center">
          <div>
            <h3 class="text-3xl font-black text-neutral-900">Regístrate</h3>
            <p class="text-neutral-600">Para recibir las mejores ofertas</p>
          </div>

          <div class="flex flex-col gap-3 md:flex-row">
            <input
              type="email"
              placeholder="Ingresa tu correo aquí"
              class="h-12 flex-1 rounded-full border border-[#d5d5d5] px-5 outline-none focus:border-[#30beef]"
            />
            <button
              type="button"
              class="h-12 rounded-full bg-gradient-to-r from-[#7dd03c] to-[#30beef] px-8 font-black text-white transition hover:opacity-90"
            >
              ¡Regístrate!
            </button>
          </div>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>
