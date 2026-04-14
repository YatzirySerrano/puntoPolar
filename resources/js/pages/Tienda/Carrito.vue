<script setup lang="ts">
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

const formatearMoneda = (valor: number) => {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN',
  }).format(valor)
}

const actualizarCantidad = (item: ItemCarrito, cantidad: number) => {
  if (cantidad < 1 || cantidad > item.stock) return

  router.patch(
    route('carrito.actualizar', item.producto_id),
    { cantidad },
    { preserveScroll: true },
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

const eliminarItem = (item: ItemCarrito) => {
  router.delete(route('carrito.eliminar', item.producto_id), {
    preserveScroll: true,
  })
}

const vaciarCarrito = () => {
  router.delete(route('carrito.vaciar'), {
    preserveScroll: true,
  })
}
</script>

<template>
  <PublicLayout>
    <section class="relative overflow-hidden">
      <img :src="heroImg" alt="Carrito" class="h-[180px] w-full object-cover md:h-[260px]" />
      <div class="absolute inset-0 bg-[#09111c]/65" />

      <div class="absolute inset-0 flex items-center justify-center">
        <div class="text-center text-white">
          <p class="text-xs font-bold tracking-[0.35em] uppercase text-white/70">
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
      <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
          <h2 class="text-3xl font-black text-neutral-900">
            Carrito de compras
          </h2>
          <p class="mt-1 text-sm text-neutral-500">
            {{ resumen.total_productos || items.length }}
            {{ (resumen.total_productos || items.length) === 1 ? 'producto' : 'productos' }} en tu carrito
          </p>
        </div>

        <div class="flex flex-wrap gap-3">
          <Link
            href="/"
            class="inline-flex items-center justify-center rounded-full border border-[#30beef] px-5 py-3 text-sm font-black text-[#30beef] transition hover:bg-[#30beef] hover:text-white"
          >
            Seguir comprando
          </Link>

          <button
            v-if="items.length"
            type="button"
            class="inline-flex items-center justify-center rounded-full border border-red-200 px-5 py-3 text-sm font-black text-red-500 transition hover:bg-red-50"
            @click="vaciarCarrito"
          >
            Vaciar carrito
          </button>
        </div>
      </div>

      <div class="grid gap-8 xl:grid-cols-[minmax(0,1.55fr)_390px]">
        <div class="overflow-hidden rounded-[28px] border border-[#d5d5d5] bg-white shadow-[0_20px_70px_rgba(9,17,28,0.06)]">
          <div class="border-b border-[#ececec] px-6 py-5">
            <h3 class="text-xl font-black text-neutral-900 md:text-2xl">
              Productos agregados
            </h3>
          </div>

          <div v-if="items.length" class="divide-y divide-[#ececec]">
            <article
              v-for="item in items"
              :key="item.producto_id"
              class="p-5 md:p-6"
            >
              <div class="grid gap-5 md:grid-cols-[120px_minmax(0,1fr)]">
                <Link
                  :href="`/productos/${item.slug}`"
                  class="group overflow-hidden rounded-2xl border border-[#e8e8e8] bg-[#f7f7f7]"
                >
                  <img
                    :src="item.imagen || '/images/placeholder-producto.png'"
                    :alt="item.nombre"
                    class="h-28 w-full object-cover transition duration-300 group-hover:scale-105 md:h-full"
                  />
                </Link>

                <div class="min-w-0">
                  <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                      <div class="mb-2 flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-[#30beef]/10 px-3 py-1 text-[11px] font-black tracking-wide text-[#0f8fba] uppercase">
                          SKU: {{ item.sku }}
                        </span>

                        <span
                          v-if="item.stock <= 3"
                          class="rounded-full bg-amber-100 px-3 py-1 text-[11px] font-black tracking-wide text-amber-700 uppercase"
                        >
                          Últimas piezas
                        </span>
                      </div>

                      <Link
                        :href="`/productos/${item.slug}`"
                        class="block text-lg font-black leading-tight text-neutral-900 transition hover:text-[#30beef] md:text-xl"
                      >
                        {{ item.nombre }}
                      </Link>

                      <p class="mt-2 text-sm text-neutral-500">
                        Disponibles: {{ item.stock }} piezas
                      </p>
                    </div>

                    <div class="shrink-0 text-left lg:text-right">
                      <div class="flex items-center gap-2 lg:justify-end">
                        <span class="text-2xl font-black text-neutral-900">
                          {{ formatearMoneda(item.precio) }}
                        </span>

                        <span
                          v-if="item.precio_comparacion && item.precio_comparacion > item.precio"
                          class="text-sm text-neutral-400 line-through"
                        >
                          {{ formatearMoneda(item.precio_comparacion) }}
                        </span>
                      </div>

                      <p class="mt-1 text-xs font-semibold text-neutral-400 uppercase">
                        Precio por unidad
                      </p>
                    </div>
                  </div>

                  <div class="mt-5 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div>
                      <p class="mb-2 text-xs font-bold tracking-[0.18em] text-neutral-400 uppercase">
                        Cantidad
                      </p>

                      <div class="inline-flex items-center rounded-2xl border border-[#d8d8d8] bg-white shadow-sm">
                        <button
                          type="button"
                          class="grid h-11 w-11 place-items-center text-lg font-black text-neutral-700 transition hover:bg-[#f5f5f5] disabled:cursor-not-allowed disabled:opacity-40"
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
                          class="h-11 w-16 border-x border-[#e5e5e5] text-center text-sm font-black text-neutral-900 outline-none"
                          @change="onInputCantidad(item, $event)"
                        />

                        <button
                          type="button"
                          class="grid h-11 w-11 place-items-center text-lg font-black text-neutral-700 transition hover:bg-[#f5f5f5] disabled:cursor-not-allowed disabled:opacity-40"
                          :disabled="item.cantidad >= item.stock"
                          @click="incrementar(item)"
                        >
                          +
                        </button>
                      </div>
                    </div>

                    <div class="flex flex-col gap-3 md:items-end">
                      <div>
                        <p class="text-xs font-bold tracking-[0.18em] text-neutral-400 uppercase">
                          Subtotal
                        </p>
                        <p class="mt-1 text-2xl font-black text-neutral-900">
                          {{ formatearMoneda(item.subtotal) }}
                        </p>
                      </div>

                      <button
                        type="button"
                        class="rounded-full border border-red-200 px-4 py-2 text-sm font-black text-red-500 transition hover:bg-red-50"
                        @click="eliminarItem(item)"
                      >
                        Eliminar producto
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </article>
          </div>

          <div v-else class="px-6 py-14 text-center md:px-10">
            <div class="mx-auto max-w-md">
              <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-r from-[#7dd03c]/15 to-[#30beef]/15 text-3xl">
                🛒
              </div>
              <h3 class="mt-5 text-2xl font-black text-neutral-900">
                Tu carrito está vacío
              </h3>
              <p class="mt-2 text-neutral-500">
                Aún no has agregado productos. Explora la tienda y elige lo que necesitas.
              </p>
              <Link
                href="/"
                class="mt-6 inline-flex items-center justify-center rounded-full bg-gradient-to-r from-[#7dd03c] to-[#30beef] px-6 py-3 text-sm font-black text-white transition hover:opacity-90"
              >
                Ir a la tienda
              </Link>
            </div>
          </div>
        </div>

        <aside class="space-y-6">
          <div class="rounded-[28px] border border-[#d5d5d5] bg-white p-6 shadow-[0_20px_70px_rgba(9,17,28,0.06)]">
            <h3 class="text-xl font-black text-neutral-900">
              Código de descuento
            </h3>
            <p class="mt-2 text-sm text-neutral-500">
              Déjalo listo para cuando conectes la lógica real de cupones.
            </p>

            <input
              type="text"
              placeholder="Ingresa tu código"
              class="mt-4 h-12 w-full rounded-2xl border border-[#d5d5d5] px-4 outline-none transition focus:border-[#30beef]"
            />

            <button
              type="button"
              class="mt-4 w-full rounded-full border border-[#30beef] px-4 py-3 font-black text-[#30beef] transition hover:bg-[#30beef] hover:text-white"
            >
              Aplicar código
            </button>
          </div>

          <div class="rounded-[28px] border border-[#d5d5d5] bg-white p-6 shadow-[0_20px_70px_rgba(9,17,28,0.06)]">
            <h3 class="text-xl font-black text-neutral-900">
              Resumen del pedido
            </h3>

            <div class="mt-5 space-y-4 text-sm">
              <div class="flex items-center justify-between">
                <span class="text-neutral-500">Productos</span>
                <span class="font-bold text-neutral-900">
                  {{ resumen.total_productos || items.length }}
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

              <div class="border-t border-[#ececec] pt-4">
                <div class="flex items-center justify-between">
                  <span class="text-lg font-black text-neutral-900">
                    Total
                  </span>
                  <span class="text-3xl font-black text-neutral-900">
                    {{ formatearMoneda(resumen.total) }}
                  </span>
                </div>
              </div>
            </div>

            <button
              type="button"
              class="mt-6 w-full rounded-full bg-gradient-to-r from-[#7dd03c] to-[#30beef] px-4 py-3 font-black text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="!items.length"
            >
              Proceder al pago
            </button>

            <p class="mt-3 text-center text-xs text-neutral-400">
              Impuestos y envío se pueden calcular en el checkout
            </p>
          </div>
        </aside>
      </div>
    </section>
  </PublicLayout>
</template>
