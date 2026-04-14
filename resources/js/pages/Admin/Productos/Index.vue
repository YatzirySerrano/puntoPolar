<script setup lang="ts">
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import PublicLayout from '@/layouts/PublicLayout.vue'
import heroImg from '@/img/hero-tienda.jpeg'

interface ItemCarrito {
  producto_id: number
  nombre: string
  slug: string
  sku: string
  imagen?: string | null
  precio: number
  precio_comparacion?: number | null
  cantidad: number
  stock: number
  subtotal: number
}

const props = defineProps<{
  items: ItemCarrito[]
  resumen: {
    subtotal: number
    envio: number
    descuento: number
    total: number
    total_productos?: number
  }
}>()

const totalItems = computed(() => props.resumen.total_productos || props.items.length)

const formatearMoneda = (valor: number) => {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN',
  }).format(valor)
}

const actualizarCantidad = (item: ItemCarrito, cantidad: number) => {
  if (cantidad < 1 || cantidad > item.stock) return

  router.patch(
    `/carrito/${item.producto_id}`,
    { cantidad },
    {
      preserveScroll: true,
    },
  )
}

const incrementar = (item: ItemCarrito) => {
  if (item.cantidad >= item.stock) return
  actualizarCantidad(item, item.cantidad + 1)
}

const disminuir = (item: ItemCarrito) => {
  if (item.cantidad <= 1) return
  actualizarCantidad(item, item.cantidad - 1)
}

const onInputCantidad = (item: ItemCarrito, event: Event) => {
  const target = event.target as HTMLInputElement
  const cantidad = Number(target.value)

  if (!Number.isFinite(cantidad)) return
  actualizarCantidad(item, cantidad)
}

const quitarDelCarrito = (item: ItemCarrito) => {
  router.delete(`/carrito/${item.producto_id}`, {
    preserveScroll: true,
  })
}

const vaciarCarrito = () => {
  router.delete('/carrito', {
    preserveScroll: true,
  })
}
</script>

<template>
  <PublicLayout>
    <section class="relative overflow-hidden">
      <img :src="heroImg" alt="Carrito" class="h-[200px] w-full object-cover md:h-[290px]" />
      <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(9,17,28,0.54)_0%,rgba(9,17,28,0.72)_100%)]" />

      <div class="absolute inset-0 flex items-center justify-center">
        <div class="px-4 text-center text-white">
          <p class="text-[11px] font-bold tracking-[0.35em] uppercase text-white/70 md:text-xs">
            Tienda Mr Lana
          </p>
          <h1 class="mt-3 text-4xl font-black uppercase md:text-6xl">
            Mi carrito
          </h1>
          <p class="mt-3 text-sm text-white/85 md:text-base">
            Revisa tus productos antes de continuar al pago
          </p>
        </div>
      </div>

      <div class="absolute bottom-0 left-0 right-0 h-10 bg-gradient-to-r from-[#7dd03c] to-[#30beef]" />
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 md:px-6 md:py-12">
      <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <h2 class="text-3xl font-black tracking-tight text-neutral-900 md:text-4xl">
            Carrito de compras
          </h2>
          <p class="mt-2 text-sm text-neutral-500 md:text-base">
            {{ totalItems }} {{ totalItems === 1 ? 'producto' : 'productos' }} en tu carrito
          </p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row">
          <Link
            href="/"
            class="inline-flex items-center justify-center rounded-full border border-[#30beef] bg-white px-6 py-3 text-sm font-black text-[#30beef] shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#30beef] hover:text-white hover:shadow-md"
          >
            Seguir comprando
          </Link>

          <button
            v-if="items.length"
            type="button"
            class="inline-flex items-center justify-center rounded-full border border-red-200 bg-white px-6 py-3 text-sm font-black text-red-500 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:bg-red-50 hover:shadow-md"
            @click="vaciarCarrito"
          >
            Vaciar carrito
          </button>
        </div>
      </div>

      <div class="grid gap-8 xl:grid-cols-[minmax(0,1.65fr)_390px]">
        <div class="overflow-hidden rounded-[30px] border border-[#dfdfdf] bg-white shadow-[0_18px_60px_rgba(9,17,28,0.06)]">
          <div class="border-b border-[#ececec] px-6 py-5 md:px-8">
            <h3 class="text-2xl font-black tracking-tight text-neutral-900">
              Productos agregados
            </h3>
          </div>

          <div v-if="items.length" class="divide-y divide-[#ececec]">
            <article
              v-for="item in items"
              :key="item.producto_id"
              class="p-5 transition-colors duration-300 hover:bg-[#fafafa] md:p-6"
            >
              <div class="grid gap-5 md:grid-cols-[150px_minmax(0,1fr)]">
                <Link
                  :href="`/productos/${item.slug}`"
                  class="group overflow-hidden rounded-[24px] border border-[#e8e8e8] bg-[#f7f7f7] shadow-sm transition-all duration-300 hover:shadow-md"
                >
                  <img
                    :src="item.imagen || '/images/placeholder-producto.png'"
                    :alt="item.nombre"
                    class="h-36 w-full object-cover transition duration-500 group-hover:scale-[1.05] md:h-full"
                  />
                </Link>

                <div class="min-w-0">
                  <div class="flex flex-col gap-5 2xl:flex-row 2xl:items-start 2xl:justify-between">
                    <div class="min-w-0">
                      <div class="mb-3 flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-[#30beef]/10 px-3 py-1 text-[11px] font-black tracking-[0.12em] text-[#0f8fba] uppercase">
                          SKU: {{ item.sku }}
                        </span>

                        <span
                          v-if="item.stock <= 3"
                          class="rounded-full bg-amber-100 px-3 py-1 text-[11px] font-black tracking-[0.12em] text-amber-700 uppercase"
                        >
                          Últimas piezas
                        </span>
                      </div>

                      <Link
                        :href="`/productos/${item.slug}`"
                        class="block text-xl font-black leading-tight text-neutral-900 transition-colors duration-300 hover:text-[#30beef] md:text-2xl"
                      >
                        {{ item.nombre }}
                      </Link>

                      <p class="mt-2 text-sm text-neutral-500">
                        Disponibles: {{ item.stock }} pieza<span v-if="item.stock !== 1">s</span>
                      </p>

                      <div class="mt-5">
                        <div class="flex items-center gap-3">
                          <span class="text-2xl font-black text-neutral-900 md:text-3xl">
                            {{ formatearMoneda(item.precio) }}
                          </span>

                          <span
                            v-if="item.precio_comparacion && item.precio_comparacion > item.precio"
                            class="text-sm text-neutral-400 line-through"
                          >
                            {{ formatearMoneda(item.precio_comparacion) }}
                          </span>
                        </div>

                        <p class="mt-1 text-xs font-bold tracking-[0.18em] text-neutral-400 uppercase">
                          Precio por unidad
                        </p>
                      </div>
                    </div>

                    <div class="grid gap-4 xl:w-[310px]">
                      <div class="rounded-[24px] border border-[#e7e7e7] bg-white p-4 shadow-sm transition duration-300 hover:shadow-md">
                        <p class="text-xs font-bold tracking-[0.18em] text-neutral-400 uppercase">
                          Cantidad
                        </p>

                        <div class="mt-3 inline-flex w-full items-center overflow-hidden rounded-2xl border border-[#d8d8d8] bg-white shadow-sm">
                          <button
                            type="button"
                            class="grid h-12 w-12 place-items-center text-lg font-black text-neutral-700 transition hover:bg-[#f5f5f5] disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="item.cantidad <= 1"
                            @click="disminuir(item)"
                          >
                            −
                          </button>

                          <input
                            type="number"
                            min="1"
                            :max="item.stock"
                            :value="item.cantidad"
                            class="h-12 w-full border-x border-[#e5e5e5] text-center text-sm font-black text-neutral-900 outline-none"
                            @change="onInputCantidad(item, $event)"
                          />

                          <button
                            type="button"
                            class="grid h-12 w-12 place-items-center text-lg font-black text-neutral-700 transition hover:bg-[#f5f5f5] disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="item.cantidad >= item.stock"
                            @click="incrementar(item)"
                          >
                            +
                          </button>
                        </div>
                      </div>

                      <div class="rounded-[24px] border border-[#e7e7e7] bg-white p-4 shadow-sm transition duration-300 hover:shadow-md">
                        <div class="flex items-end justify-between gap-3">
                          <div>
                            <p class="text-xs font-bold tracking-[0.18em] text-neutral-400 uppercase">
                              Subtotal
                            </p>
                            <p class="mt-2 text-2xl font-black text-neutral-900 md:text-3xl">
                              {{ formatearMoneda(item.subtotal) }}
                            </p>
                          </div>

                          <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-full border border-red-200 px-4 py-2 text-sm font-black text-red-500 transition-all duration-300 hover:bg-red-50 hover:shadow-sm"
                            @click="quitarDelCarrito(item)"
                          >
                            Quitar
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </article>
          </div>

          <div v-else class="px-6 py-16 text-center md:px-10">
            <div class="mx-auto max-w-md">
              <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-r from-[#7dd03c]/15 to-[#30beef]/15 text-3xl shadow-sm">
                🛒
              </div>

              <h3 class="mt-5 text-2xl font-black text-neutral-900">
                Tu carrito está vacío
              </h3>

              <p class="mt-2 text-neutral-500">
                Explora el catálogo y agrega los productos que quieras revisar o comprar.
              </p>

              <Link
                href="/"
                class="mt-6 inline-flex items-center justify-center rounded-full bg-gradient-to-r from-[#7dd03c] to-[#30beef] px-6 py-3 text-sm font-black text-white transition-all duration-300 hover:-translate-y-0.5 hover:opacity-90 hover:shadow-md"
              >
                Ir a la tienda
              </Link>
            </div>
          </div>
        </div>

        <aside class="space-y-6">
          <div class="rounded-[30px] border border-[#dfdfdf] bg-white p-6 shadow-[0_18px_60px_rgba(9,17,28,0.06)] transition-shadow duration-300 hover:shadow-[0_22px_70px_rgba(9,17,28,0.08)]">
            <h3 class="text-xl font-black text-neutral-900">
              Código de descuento
            </h3>

            <p class="mt-2 text-sm leading-6 text-neutral-500">
              La interfaz queda lista para conectar cupones reales más adelante.
            </p>

            <input
              type="text"
              placeholder="Ingresa tu código"
              class="mt-4 h-12 w-full rounded-2xl border border-[#d5d5d5] bg-white px-4 text-sm text-neutral-900 outline-none transition-all duration-300 placeholder:text-neutral-400 focus:border-[#30beef] focus:ring-4 focus:ring-[#30beef]/10"
            />

            <button
              type="button"
              class="mt-4 w-full rounded-full border border-[#30beef] px-4 py-3 font-black text-[#30beef] transition-all duration-300 hover:bg-[#30beef] hover:text-white hover:shadow-md"
            >
              Aplicar código
            </button>
          </div>

          <div class="rounded-[30px] border border-[#dfdfdf] bg-white p-6 shadow-[0_18px_60px_rgba(9,17,28,0.06)] transition-shadow duration-300 hover:shadow-[0_22px_70px_rgba(9,17,28,0.08)]">
            <h3 class="text-xl font-black text-neutral-900">
              Resumen del pedido
            </h3>

            <div class="mt-6 space-y-4 text-sm">
              <div class="flex items-center justify-between">
                <span class="text-neutral-500">Productos</span>
                <span class="font-bold text-neutral-900">
                  {{ totalItems }}
                </span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-neutral-500">Subtotal</span>
                <span class="font-bold text-neutral-900">
                  {{ formatearMoneda(resumen.subtotal) }}
                </span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-neutral-500">Envío</span>
                <span class="font-bold text-neutral-900">
                  {{ formatearMoneda(resumen.envio) }}
                </span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-neutral-500">Descuento</span>
                <span class="font-bold text-neutral-900">
                  {{ formatearMoneda(resumen.descuento) }}
                </span>
              </div>

              <div class="border-t border-[#ececec] pt-5">
                <div class="flex items-end justify-between gap-3">
                  <span class="text-lg font-black text-neutral-900">
                    Total
                  </span>
                  <span class="text-3xl font-black tracking-tight text-neutral-900">
                    {{ formatearMoneda(resumen.total) }}
                  </span>
                </div>
              </div>
            </div>

            <button
              type="button"
              class="mt-6 w-full rounded-full bg-gradient-to-r from-[#7dd03c] to-[#30beef] px-4 py-3.5 font-black text-white transition-all duration-300 hover:-translate-y-0.5 hover:opacity-90 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="!items.length"
            >
              Proceder al pago
            </button>

            <p class="mt-3 text-center text-xs leading-5 text-neutral-400">
              El envío, impuestos o promociones se pueden ajustar en el checkout
            </p>
          </div>
        </aside>
      </div>
    </section>
  </PublicLayout>
</template>
