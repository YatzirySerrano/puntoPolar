<script setup lang="ts">
import { router } from '@inertiajs/vue3'

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
}

const props = defineProps<{
  producto: Producto
}>()

const formatearMoneda = (valor: number | string) => {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN',
  }).format(Number(valor))
}

const agregarAlCarrito = () => {
  router.post(route('carrito.agregar'), {
    producto_id: props.producto.id,
    cantidad: 1,
  }, {
    preserveScroll: true,
  })
}
</script>

<template>
  <div class="group overflow-hidden rounded-2xl border border-[#d5d5d5] bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
    <div class="aspect-square overflow-hidden bg-[#f7f7f7]">
      <img
        :src="producto.imagen_principal || '/images/placeholder-producto.png'"
        :alt="producto.nombre"
        class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
      />
    </div>

    <div class="space-y-3 p-4">
      <div class="min-h-[52px]">
        <h3 class="line-clamp-2 text-sm font-extrabold uppercase text-neutral-900">
          {{ producto.nombre }}
        </h3>
      </div>

      <div class="flex items-center gap-2">
        <span class="text-lg font-black text-neutral-900">
          {{ formatearMoneda(producto.precio) }}
        </span>

        <span
          v-if="producto.precio_comparacion"
          class="text-sm text-neutral-400 line-through"
        >
          {{ formatearMoneda(producto.precio_comparacion) }}
        </span>
      </div>

      <div class="grid grid-cols-2 gap-2">
        <button
          type="button"
          class="rounded-full border border-[#30beef] px-4 py-2 text-sm font-bold text-[#30beef] transition hover:bg-[#30beef] hover:text-white"
        >
          Ver más
        </button>

        <button
          type="button"
          class="rounded-full bg-[#7dd03c] px-4 py-2 text-sm font-bold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:bg-[#d5d5d5]"
          :disabled="producto.stock < 1"
          @click="agregarAlCarrito"
        >
          Añadir
        </button>
      </div>
    </div>
  </div>
</template>
