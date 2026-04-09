<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import PublicLayout from '@/layouts/PublicLayout.vue'
import heroImg from '@/img/hero-tienda.jpeg'

interface ItemCarrito {
  producto_id: number
  nombre: string
  slug: string
  sku: string
  imagen?: string | null
  precio: number
  cantidad: number
  stock: number
  subtotal: number
}

defineProps<{
  items: ItemCarrito[]
  resumen: {
    subtotal: number
    envio: number
    descuento: number
    total: number
  }
}>()

const formatearMoneda = (valor: number) => {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN',
  }).format(valor)
}

const actualizarCantidad = (item: ItemCarrito, event: Event) => {
  const target = event.target as HTMLInputElement
  const cantidad = Number(target.value)

  if (cantidad < 1) return

  router.patch(route('carrito.actualizar', item.producto_id), {
    cantidad,
  }, {
    preserveScroll: true,
  })
}

const eliminarItem = (item: ItemCarrito) => {
  router.delete(route('carrito.eliminar', item.producto_id), {
    preserveScroll: true,
  })
}
</script>

<template>
  <PublicLayout>
    <section class="relative overflow-hidden">
      <img :src="heroImg" alt="Carrito" class="h-[180px] w-full object-cover md:h-[260px]" />
      <div class="absolute inset-0 bg-black/40" />

      <div class="absolute inset-0 flex items-center justify-center">
        <h1 class="text-4xl font-black uppercase text-white md:text-6xl">Carrito</h1>
      </div>

      <div class="absolute bottom-0 left-0 right-0 h-10 bg-gradient-to-r from-[#7dd03c] to-[#30beef]" />
    </section>

    <section class="mx-auto max-w-7xl px-4 py-12 md:px-6">
      <div class="grid gap-8 xl:grid-cols-[1.5fr_420px]">
        <div class="overflow-hidden rounded-3xl border border-[#d5d5d5] bg-white">
          <div class="border-b border-[#d5d5d5] px-6 py-5">
            <h2 class="text-3xl font-black text-neutral-900">Carrito de compras</h2>
          </div>

          <div v-if="items.length" class="divide-y divide-[#d5d5d5]">
            <div
              v-for="item in items"
              :key="item.producto_id"
              class="grid gap-4 p-6 md:grid-cols-[100px_1fr_120px_110px_110px]"
            >
              <div class="overflow-hidden rounded-2xl border border-[#d5d5d5] bg-[#f8f8f8]">
                <img
                  :src="item.imagen || '/images/placeholder-producto.png'"
                  :alt="item.nombre"
                  class="h-24 w-full object-cover"
                />
              </div>

              <div class="space-y-2">
                <h3 class="text-lg font-black uppercase text-neutral-900">
                  {{ item.nombre }}
                </h3>
                <p class="text-sm text-neutral-500">SKU: {{ item.sku }}</p>
                <p class="text-sm text-neutral-500">Stock disponible: {{ item.stock }}</p>
              </div>

              <div class="flex items-center">
                <span class="text-lg font-black text-neutral-900">
                  {{ formatearMoneda(item.precio) }}
                </span>
              </div>

              <div class="flex items-center">
                <input
                  type="number"
                  min="1"
                  :max="item.stock"
                  :value="item.cantidad"
                  class="h-11 w-24 rounded-xl border border-[#d5d5d5] px-3 text-center outline-none focus:border-[#30beef]"
                  @change="actualizarCantidad(item, $event)"
                />
              </div>

              <div class="flex items-center justify-between gap-3 md:block">
                <div class="text-lg font-black text-neutral-900">
                  {{ formatearMoneda(item.subtotal) }}
                </div>

                <button
                  type="button"
                  class="mt-2 rounded-full border border-red-200 px-4 py-2 text-sm font-bold text-red-500 transition hover:bg-red-50"
                  @click="eliminarItem(item)"
                >
                  Eliminar
                </button>
              </div>
            </div>
          </div>

          <div v-else class="p-10 text-center">
            <h3 class="text-2xl font-black text-neutral-900">Tu carrito está vacío</h3>
            <p class="mt-2 text-neutral-500">Agrega productos para continuar.</p>
          </div>
        </div>

        <div class="space-y-6">
          <div class="rounded-3xl border border-[#d5d5d5] bg-white p-6">
            <h3 class="mb-4 text-xl font-black text-neutral-900">Código de descuento</h3>
            <input
              type="text"
              placeholder="Ingresa tu código"
              class="h-12 w-full rounded-xl border border-[#d5d5d5] px-4 outline-none focus:border-[#30beef]"
            />
            <button
              type="button"
              class="mt-4 w-full rounded-full border border-[#30beef] px-4 py-3 font-black text-[#30beef] transition hover:bg-[#30beef] hover:text-white"
            >
              Aplicar código
            </button>
          </div>

          <div class="rounded-3xl border border-[#d5d5d5] bg-white p-6">
            <h3 class="mb-5 text-xl font-black text-neutral-900">Resumen</h3>

            <div class="space-y-4 text-sm">
              <div class="flex items-center justify-between">
                <span class="text-neutral-500">Subtotal</span>
                <span class="font-bold text-neutral-900">{{ formatearMoneda(resumen.subtotal) }}</span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-neutral-500">Envío</span>
                <span class="font-bold text-neutral-900">{{ formatearMoneda(resumen.envio) }}</span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-neutral-500">Descuento</span>
                <span class="font-bold text-neutral-900">{{ formatearMoneda(resumen.descuento) }}</span>
              </div>

              <div class="border-t border-[#d5d5d5] pt-4">
                <div class="flex items-center justify-between">
                  <span class="text-xl font-black text-neutral-900">Total</span>
                  <span class="text-2xl font-black text-neutral-900">{{ formatearMoneda(resumen.total) }}</span>
                </div>
              </div>
            </div>

            <button
              type="button"
              class="mt-6 w-full rounded-full bg-gradient-to-r from-[#7dd03c] to-[#30beef] px-4 py-3 font-black text-white transition hover:opacity-90"
            >
              Proceder a pago
            </button>
          </div>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>
