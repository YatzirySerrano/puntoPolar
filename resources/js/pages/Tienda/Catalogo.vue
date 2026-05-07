<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import PublicLayout from '@/layouts/PublicLayout.vue'
import heroImg from '@/img/hero-tienda.jpeg'

interface Categoria {
  id: number
  nombre: string
  slug: string
  imagen?: string | null
}

interface Marca {
  id: number
  nombre: string
  slug?: string
}

interface OfertaInfo {
  id: number
  nombre: string
  tipo: string
  valor: number
}

interface Producto {
  id: number
  nombre: string
  slug: string
  sku: string
  descripcion?: string | null
  precio: number
  precio_original?: number | null
  precio_comparacion?: number | null
  precio_final?: number | null
  descuento_oferta?: number
  tiene_oferta?: boolean
  oferta?: OfertaInfo | null
  stock: number
  imagen_principal?: string | null
  destacado?: boolean
  categoria_id?: number | null
  categoria?: Categoria | null
  marca?: Marca | null
}

const props = defineProps<{
  categorias: Categoria[]
  productos: Producto[]
  filtros?: {
    buscar?: string | null
    categoria?: string | number | null
  }
}>()

const selectedCategoryId = ref<number | null>(
  props.filtros?.categoria ? Number(props.filtros.categoria) : null,
)
const selectedBrandId = ref<number | null>(null)
const searchText = ref(props.filtros?.buscar ?? '')
const sortBy = ref<
  'relevancia' | 'precio_asc' | 'precio_desc' | 'nombre' | 'ofertas'
>('relevancia')

const priceMin = ref<string>('')
const priceMax = ref<string>('')
const onlyOffers = ref(false)
const pricePreset = ref<'all' | '0-500' | '500-1000' | '1000-3000' | '3000+' >('all')

const animatingProductId = ref<number | null>(null)
const toast = ref<{ show: boolean; text: string }>({
  show: false,
  text: '',
})

const categoriasConConteo = computed(() => {
  return (props.categorias ?? []).map((categoria) => {
    const count = (props.productos ?? []).filter((producto) => {
      const productCategoryId = producto.categoria?.id ?? producto.categoria_id ?? null
      return productCategoryId === categoria.id
    }).length

    return {
      ...categoria,
      count,
    }
  })
})

const marcasDisponibles = computed(() => {
  const map = new Map<number, Marca>()

  for (const producto of props.productos ?? []) {
    if (producto.marca?.id && producto.marca?.nombre) {
      map.set(producto.marca.id, {
        id: producto.marca.id,
        nombre: producto.marca.nombre,
        slug: producto.marca.slug,
      })
    }
  }

  return Array.from(map.values()).sort((a, b) => a.nombre.localeCompare(b.nombre))
})

const filteredProducts = computed(() => {
  const term = searchText.value.trim().toLowerCase()

  let items = [...(props.productos ?? [])].filter((producto) => Number(producto.stock) > 0)

  if (selectedCategoryId.value) {
    items = items.filter((producto) => {
      const productCategoryId = producto.categoria?.id ?? producto.categoria_id ?? null
      return productCategoryId === selectedCategoryId.value
    })
  }

  if (selectedBrandId.value) {
    items = items.filter((producto) => {
      return (producto.marca?.id ?? null) === selectedBrandId.value
    })
  }

  if (term) {
    items = items.filter((producto) => {
      const searchable = [
        producto.nombre,
        producto.sku,
        producto.descripcion,
        producto.categoria?.nombre,
        producto.marca?.nombre,
        producto.oferta?.nombre,
      ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase()

      return searchable.includes(term)
    })
  }

  if (onlyOffers.value) {
    items = items.filter((producto) => !!producto.tiene_oferta)
  }

  if (pricePreset.value !== 'all') {
    items = items.filter((producto) => {
      const precio = Number(producto.precio)

      if (pricePreset.value === '0-500') return precio >= 0 && precio <= 500
      if (pricePreset.value === '500-1000') return precio > 500 && precio <= 1000
      if (pricePreset.value === '1000-3000') return precio > 1000 && precio <= 3000
      if (pricePreset.value === '3000+') return precio > 3000

      return true
    })
  }

  const min = priceMin.value !== '' ? Number(priceMin.value) : null
  const max = priceMax.value !== '' ? Number(priceMax.value) : null

  if (min !== null && !Number.isNaN(min)) {
    items = items.filter((producto) => Number(producto.precio) >= min)
  }

  if (max !== null && !Number.isNaN(max)) {
    items = items.filter((producto) => Number(producto.precio) <= max)
  }

  if (sortBy.value === 'precio_asc') {
    items.sort((a, b) => Number(a.precio) - Number(b.precio))
  } else if (sortBy.value === 'precio_desc') {
    items.sort((a, b) => Number(b.precio) - Number(a.precio))
  } else if (sortBy.value === 'nombre') {
    items.sort((a, b) => a.nombre.localeCompare(b.nombre))
  } else if (sortBy.value === 'ofertas') {
    items.sort((a, b) => Number(!!b.tiene_oferta) - Number(!!a.tiene_oferta))
  }

  return items
})

const resumenCatalogo = computed(() => {
  const total = filteredProducts.value.length
  const conOferta = filteredProducts.value.filter((p) => p.tiene_oferta).length

  const precios = filteredProducts.value.map((p) => Number(p.precio)).filter((p) => !Number.isNaN(p))
  const minimo = precios.length ? Math.min(...precios) : 0
  const maximo = precios.length ? Math.max(...precios) : 0

  return {
    total,
    conOferta,
    minimo,
    maximo,
  }
})

function formatPrice(value: number | string | null | undefined) {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN',
    maximumFractionDigits: 2,
  }).format(Number(value ?? 0))
}

function setCategory(categoryId: number | null) {
  selectedCategoryId.value = categoryId
}

function setBrand(brandId: number | null) {
  selectedBrandId.value = brandId
}

function resetFilters() {
  selectedCategoryId.value = null
  selectedBrandId.value = null
  searchText.value = ''
  sortBy.value = 'relevancia'
  priceMin.value = ''
  priceMax.value = ''
  onlyOffers.value = false
  pricePreset.value = 'all'
}

function showToast(text: string) {
  toast.value = { show: true, text }

  window.clearTimeout((showToast as typeof showToast & { timer?: number }).timer)
  ;(showToast as typeof showToast & { timer?: number }).timer = window.setTimeout(() => {
    toast.value.show = false
  }, 2200)
}

function triggerBubble(productId: number) {
  animatingProductId.value = productId

  window.setTimeout(() => {
    if (animatingProductId.value === productId) {
      animatingProductId.value = null
    }
  }, 750)
}

function addToCart(producto: Producto) {
  triggerBubble(producto.id)

  router.post(
    '/carrito/agregar',
    {
      producto_id: producto.id,
      cantidad: 1,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        showToast(`"${producto.nombre}" se agregó al carrito`)
      },
      onError: () => {
        showToast('No se pudo agregar el producto al carrito')
      },
    },
  )
}
</script>

<template>
  <PublicLayout>
    <Head title="Catálogo de productos" />

    <div class="bg-[linear-gradient(180deg,#ffffff_0%,#f7fbff_100%)]">
      <section class="px-4 pb-14 pt-8 md:px-8 md:pt-10">
        <div class="mx-auto w-full max-w-[1550px]">
          <div class="mb-8 flex flex-col gap-5 rounded-[34px] border border-neutral-200 bg-white/90 px-5 py-6 shadow-[0_20px_50px_rgba(17,20,44,0.06)] backdrop-blur md:px-7 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">

              <h1 class="mt-4 text-3xl font-black tracking-tight text-neutral-900 md:text-5xl">
                Encuentra justo lo que buscas
              </h1>

              <p class="mt-3 text-sm leading-7 text-neutral-600 md:text-base">
                Explora por categoría y rango de precio. Compara opciones, revisa promociones y agrega al carrito en segundos.
              </p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:min-w-[360px]">
              <div class="rounded-[24px] border border-neutral-200 bg-[#f8fbff] px-5 py-4 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-sm">
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-neutral-400">
                  Resultados
                </p>
                <p class="mt-2 text-2xl font-black text-neutral-900">
                  {{ resumenCatalogo.total }}
                </p>
              </div>

              <div class="rounded-[24px] border border-neutral-200 bg-[#f8fbff] px-5 py-4 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-sm">
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-neutral-400">
                  Rango visible
                </p>
                <p class="mt-2 text-sm font-black text-neutral-900">
                  {{ formatPrice(resumenCatalogo.minimo) }} - {{ formatPrice(resumenCatalogo.maximo) }}
                </p>
              </div>
            </div>
          </div>

          <div class="grid gap-8 xl:grid-cols-[330px_minmax(0,1fr)]">
            <aside class="space-y-5">
              <div class="rounded-[30px] border border-neutral-200 bg-white p-5 shadow-[0_18px_45px_rgba(17,20,44,0.06)] transition-all duration-300 hover:shadow-[0_24px_55px_rgba(17,20,44,0.08)]">
                <h3 class="text-lg font-black text-neutral-900">
                  Buscar
                </h3>

                <div class="mt-4 rounded-2xl border border-[var(--brand-gray)] bg-[#f8f8f8] px-4 py-3 transition-all duration-300 focus-within:border-[var(--brand-blue)] focus-within:bg-white focus-within:shadow-sm">
                  <input
                    v-model="searchText"
                    type="text"
                    placeholder="Nombre o SKU..."
                    class="w-full bg-transparent text-sm text-neutral-700 outline-none placeholder:text-neutral-400"
                  />
                </div>
              </div>

              <div class="rounded-[30px] border border-neutral-200 bg-white p-5 shadow-[0_18px_45px_rgba(17,20,44,0.06)] transition-all duration-300 hover:shadow-[0_24px_55px_rgba(17,20,44,0.08)]">
                <h3 class="text-lg font-black text-neutral-900">
                  Ordenar
                </h3>

                <select
                  v-model="sortBy"
                  class="mt-4 h-12 w-full rounded-2xl border border-[var(--brand-gray)] bg-white px-4 text-sm font-semibold text-neutral-700 outline-none transition-all duration-300 hover:border-[var(--brand-blue)] focus:border-[var(--brand-blue)]"
                >
                  <option value="relevancia">Relevancia</option>
                  <option value="precio_asc">Precio: menor a mayor</option>
                  <option value="precio_desc">Precio: mayor a menor</option>
                  <option value="nombre">Nombre</option>
                  <option value="ofertas">Primero promociones</option>
                </select>
              </div>

              <div class="rounded-[30px] border border-neutral-200 bg-white p-5 shadow-[0_18px_45px_rgba(17,20,44,0.06)] transition-all duration-300 hover:shadow-[0_24px_55px_rgba(17,20,44,0.08)]">
                <div class="mb-4 flex items-center justify-between gap-3">
                  <h3 class="text-lg font-black text-neutral-900">
                    Categorías
                  </h3>

                  <button
                    type="button"
                    class="text-xs font-black uppercase tracking-[0.14em] text-[var(--brand-blue)] transition hover:opacity-70"
                    @click="selectedCategoryId = null"
                  >
                    Limpiar
                  </button>
                </div>

                <div class="flex flex-wrap gap-2">
                  <button
                    type="button"
                    class="rounded-full px-4 py-2 text-sm font-black transition-all duration-300"
                    :class="selectedCategoryId === null ? 'bg-[#11142C] text-white shadow-sm' : 'border border-neutral-200 bg-white text-neutral-700 hover:border-[var(--brand-blue)] hover:text-[var(--brand-blue)]'"
                    @click="setCategory(null)"
                  >
                    Todas
                  </button>

                  <button
                    v-for="categoria in categoriasConConteo"
                    :key="`filter-${categoria.id}`"
                    type="button"
                    class="rounded-full px-4 py-2 text-sm font-black transition-all duration-300"
                    :class="selectedCategoryId === categoria.id ? 'bg-[#11142C] text-white shadow-sm' : 'border border-neutral-200 bg-white text-neutral-700 hover:border-[var(--brand-blue)] hover:text-[var(--brand-blue)]'"
                    @click="setCategory(categoria.id)"
                  >
                    {{ categoria.nombre }}
                  </button>
                </div>
              </div>

              <!--<div class="rounded-[30px] border border-neutral-200 bg-white p-5 shadow-[0_18px_45px_rgba(17,20,44,0.06)] transition-all duration-300 hover:shadow-[0_24px_55px_rgba(17,20,44,0.08)]">
                <div class="mb-4 flex items-center justify-between gap-3">
                  <h3 class="text-lg font-black text-neutral-900">
                    Marcas
                  </h3>

                  <button
                    type="button"
                    class="text-xs font-black uppercase tracking-[0.14em] text-[var(--brand-blue)] transition hover:opacity-70"
                    @click="setBrand(null)"
                  >
                    Limpiar
                  </button>
                </div>

                <div class="flex flex-wrap gap-2">
                  <button
                    type="button"
                    class="rounded-full px-4 py-2 text-sm font-black transition-all duration-300"
                    :class="selectedBrandId === null ? 'bg-[#11142C] text-white shadow-sm' : 'border border-neutral-200 bg-white text-neutral-700 hover:border-[var(--brand-blue)] hover:text-[var(--brand-blue)]'"
                    @click="setBrand(null)"
                  >
                    Todas
                  </button>

                  <button
                    v-for="marca in marcasDisponibles"
                    :key="marca.id"
                    type="button"
                    class="rounded-full px-4 py-2 text-sm font-black transition-all duration-300"
                    :class="selectedBrandId === marca.id ? 'bg-[#11142C] text-white shadow-sm' : 'border border-neutral-200 bg-white text-neutral-700 hover:border-[var(--brand-blue)] hover:text-[var(--brand-blue)]'"
                    @click="setBrand(marca.id)"
                  >
                    {{ marca.nombre }}
                  </button>
                </div>
              </div>
              -->
              <div class="rounded-[30px] border border-neutral-200 bg-white p-5 shadow-[0_18px_45px_rgba(17,20,44,0.06)] transition-all duration-300 hover:shadow-[0_24px_55px_rgba(17,20,44,0.08)]">
                <h3 class="text-lg font-black text-neutral-900">
                  Precio
                </h3>

                <div class="mt-4 grid grid-cols-2 gap-3">
                  <input
                    v-model="priceMin"
                    type="number"
                    min="0"
                    placeholder="Mínimo"
                    class="h-12 rounded-2xl border border-neutral-200 bg-white px-4 text-sm font-semibold text-neutral-700 outline-none transition-all duration-300 focus:border-[var(--brand-blue)]"
                  />

                  <input
                    v-model="priceMax"
                    type="number"
                    min="0"
                    placeholder="Máximo"
                    class="h-12 rounded-2xl border border-neutral-200 bg-white px-4 text-sm font-semibold text-neutral-700 outline-none transition-all duration-300 focus:border-[var(--brand-blue)]"
                  />
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                  <button
                    type="button"
                    class="rounded-full px-4 py-2 text-sm font-black transition-all duration-300"
                    :class="pricePreset === 'all' ? 'bg-[#11142C] text-white shadow-sm' : 'border border-neutral-200 bg-white text-neutral-700 hover:border-[var(--brand-blue)] hover:text-[var(--brand-blue)]'"
                    @click="pricePreset = 'all'"
                  >
                    Todos
                  </button>

                  <button
                    type="button"
                    class="rounded-full px-4 py-2 text-sm font-black transition-all duration-300"
                    :class="pricePreset === '0-500' ? 'bg-[#11142C] text-white shadow-sm' : 'border border-neutral-200 bg-white text-neutral-700 hover:border-[var(--brand-blue)] hover:text-[var(--brand-blue)]'"
                    @click="pricePreset = '0-500'"
                  >
                    $0 - $500
                  </button>

                  <button
                    type="button"
                    class="rounded-full px-4 py-2 text-sm font-black transition-all duration-300"
                    :class="pricePreset === '500-1000' ? 'bg-[#11142C] text-white shadow-sm' : 'border border-neutral-200 bg-white text-neutral-700 hover:border-[var(--brand-blue)] hover:text-[var(--brand-blue)]'"
                    @click="pricePreset = '500-1000'"
                  >
                    $500 - $1,000
                  </button>

                  <button
                    type="button"
                    class="rounded-full px-4 py-2 text-sm font-black transition-all duration-300"
                    :class="pricePreset === '1000-3000' ? 'bg-[#11142C] text-white shadow-sm' : 'border border-neutral-200 bg-white text-neutral-700 hover:border-[var(--brand-blue)] hover:text-[var(--brand-blue)]'"
                    @click="pricePreset = '1000-3000'"
                  >
                    $1,000 - $3,000
                  </button>

                  <button
                    type="button"
                    class="rounded-full px-4 py-2 text-sm font-black transition-all duration-300"
                    :class="pricePreset === '3000+' ? 'bg-[#11142C] text-white shadow-sm' : 'border border-neutral-200 bg-white text-neutral-700 hover:border-[var(--brand-blue)] hover:text-[var(--brand-blue)]'"
                    @click="pricePreset = '3000+'"
                  >
                    Más de $3,000
                  </button>
                </div>
              </div>

              <div class="rounded-[30px] border border-neutral-200 bg-white p-5 shadow-[0_18px_45px_rgba(17,20,44,0.06)] transition-all duration-300 hover:shadow-[0_24px_55px_rgba(17,20,44,0.08)]">
                <label class="flex items-center justify-between gap-4 rounded-[22px] border border-neutral-200 bg-[#f8fbff] px-4 py-4 transition-all duration-300 hover:border-[var(--brand-blue)]/30">
                  <div>
                    <p class="text-sm font-black text-neutral-900">
                      Solo promociones
                    </p>
                    <p class="mt-1 text-xs text-neutral-500">
                      Mostrar únicamente productos con oferta activa
                    </p>
                  </div>

                  <input
                    v-model="onlyOffers"
                    type="checkbox"
                    class="h-5 w-5 rounded border-neutral-300 text-[var(--brand-blue)] focus:ring-[var(--brand-blue)]"
                  />
                </label>
              </div>
            </aside>

            <div>
              <div class="mb-6 flex flex-col gap-3 rounded-[30px] border border-neutral-200 bg-white p-5 shadow-[0_18px_45px_rgba(17,20,44,0.06)] md:flex-row md:items-center md:justify-between">
                <div>
                  <p class="text-sm font-semibold text-neutral-500">
                    Mostrando
                    <span class="font-black text-neutral-900">{{ filteredProducts.length }}</span>
                    producto<span v-if="filteredProducts.length !== 1">s</span>
                  </p>

                  <p v-if="selectedCategoryId || selectedBrandId || onlyOffers" class="mt-1 text-sm text-neutral-500">
                    Filtros activos aplicados al catálogo
                  </p>
                </div>

                <div class="flex flex-wrap gap-2">
                  <span class="inline-flex items-center rounded-full bg-[linear-gradient(135deg,#30BEEF_0%,#0B5FA5_100%)] px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-white shadow-[0_10px_24px_rgba(48,190,239,0.22)]">
                    {{ resumenCatalogo.conOferta }} con promoción
                  </span>
                  <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-full border border-neutral-200 bg-white px-5 py-3 text-sm font-black text-[#11142C] transition hover:border-[var(--brand-blue)] hover:bg-[var(--brand-blue)]/8 hover:text-[var(--brand-blue)]"
                    @click="resetFilters"
                  >
                    Restablecer
                  </button>
                </div>
              </div>

              <div v-if="filteredProducts.length" class="grid gap-6 sm:grid-cols-2 2xl:grid-cols-3">
                <div
                  v-for="producto in filteredProducts"
                  :key="producto.id"
                  class="group [perspective:1800px]"
                >
                  <div
                    class="relative h-[560px] w-full rounded-[32px] transition-transform duration-700 [transform-style:preserve-3d] group-hover:[transform:rotateY(180deg)]"
                  >
                    <article
                      class="absolute inset-0 overflow-hidden rounded-[32px] border border-neutral-200 bg-white shadow-[0_18px_50px_rgba(17,20,44,0.08)] transition-all duration-300 [backface-visibility:hidden] group-hover:-translate-y-1 group-hover:shadow-[0_28px_70px_rgba(17,20,44,0.12)]"
                    >
                      <div class="relative h-[285px] overflow-hidden bg-[var(--brand-soft)]">
                        <img
                          :src="producto.imagen_principal || heroImg"
                          :alt="producto.nombre"
                          class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-[1.08]"
                        />

                        <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(17,20,44,0.02)_0%,rgba(17,20,44,0.28)_100%)]" />

                        <div class="absolute left-4 top-4 flex flex-wrap gap-2">
                          <span class="rounded-full bg-white/92 px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-[#11142C]">
                            {{ producto.categoria?.nombre || 'Producto' }}
                          </span>

                          <span
                            v-if="producto.tiene_oferta"
                            class="rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-emerald-700"
                          >
                            Oferta activa
                          </span>
                        </div>

                        <div class="absolute right-4 top-4 inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-white/30 bg-white/15 text-white backdrop-blur transition-all duration-300 group-hover:scale-105">
                          ⟳
                        </div>
                      </div>

                      <div class="flex min-h-[185px] flex-col p-5">
                        <div>
                            <h3 class="line-clamp-2 text-xl font-black leading-tight text-neutral-900">
                            {{ producto.nombre }}
                            </h3>
                        </div>

                        <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-neutral-500">
                            <span>SKU: {{ producto.sku }}</span>
                            <!--<span v-if="producto.marca?.nombre">• {{ producto.marca.nombre }}</span>-->
                        </div>

                        <p class="mt-2 line-clamp-2 text-sm leading-5 text-neutral-500">
                            {{ producto.descripcion || 'Producto disponible en catálogo.' }}
                        </p>

                        <div class="mt-auto pt-5">
                          <div class="flex items-end justify-between gap-3">
                            <div>
                              <p
                                v-if="producto.precio_original && producto.precio_original > producto.precio"
                                class="text-sm text-neutral-400 line-through"
                              >
                                {{ formatPrice(producto.precio_original) }}
                              </p>
                              <p
                                v-else-if="producto.precio_comparacion && Number(producto.precio_comparacion) > Number(producto.precio)"
                                class="text-sm text-neutral-400 line-through"
                              >
                                {{ formatPrice(producto.precio_comparacion) }}
                              </p>

                              <p class="text-2xl font-black text-[#11142C]">
                                {{ formatPrice(producto.precio) }}
                              </p>

                              <p
                                v-if="producto.tiene_oferta && producto.oferta"
                                class="mt-1 text-xs font-bold uppercase tracking-[0.16em] text-emerald-600"
                              >
                                {{ producto.oferta.nombre }}
                              </p>
                            </div>

                            <div class="text-right">
                              <p class="text-xs font-bold uppercase tracking-[0.16em] text-neutral-400">
                                Stock
                              </p>
                              <p class="mt-1 text-sm font-black text-neutral-900">
                                {{ producto.stock }}
                              </p>
                            </div>
                          </div>

                          <div class="mt-5 grid grid-cols-2 gap-3">
                            <Link
                              :href="`/productos/${producto.slug}`"
                              class="inline-flex h-12 items-center justify-center rounded-full border border-[var(--brand-blue)] px-4 text-sm font-black text-[var(--brand-blue)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-[var(--brand-blue)] hover:text-white hover:shadow-sm"
                            >
                              Ver detalle
                            </Link>

                            <button
                              type="button"
                              class="relative inline-flex h-12 items-center justify-center rounded-full bg-[linear-gradient(135deg,#30BEEF_0%,#0B5FA5_100%)] px-4 text-sm font-black text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-[var(--brand-green)] hover:text-[#11142C] hover:shadow-[0_14px_30px_rgba(125,208,60,0.20)] disabled:cursor-not-allowed disabled:bg-neutral-300 disabled:text-white"
                              :disabled="producto.stock < 1"
                              @click="addToCart(producto)"
                            >
                              <span v-if="animatingProductId === producto.id" class="pointer-events-none absolute inset-0">
                                <span class="bubble bubble-1" />
                                <span class="bubble bubble-2" />
                                <span class="bubble bubble-3" />
                                <span class="bubble bubble-4" />
                                <span class="bubble bubble-5" />
                              </span>

                              <span class="relative z-10">
                                Agregar
                              </span>
                            </button>
                          </div>
                        </div>
                      </div>
                    </article>

                    <article
                      class="absolute inset-0 overflow-hidden rounded-[32px] border border-sky-200 bg-[linear-gradient(180deg,#031B3F_0%,#063B78_55%,#0A8DC4_100%)] text-white shadow-[0_18px_50px_rgba(48,190,239,0.18)] [transform:rotateY(180deg)] [backface-visibility:hidden]"
                    >
                      <div class="flex h-full flex-col p-6">
                        <div class="flex items-start justify-between gap-3">
                          <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/50">
                              Vista rápida
                            </p>
                            <h3 class="mt-2 text-2xl font-black leading-tight">
                              {{ producto.nombre }}
                            </h3>
                          </div>

                          <div class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-white">
                            ✦
                          </div>
                        </div>

                        <div class="mt-6 grid gap-4">
                          <div class="rounded-[24px] border border-white/10 bg-white/10 p-4">
                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/45">
                              Precio actual
                            </p>
                            <p class="mt-2 text-3xl font-black">
                              {{ formatPrice(producto.precio) }}
                            </p>

                            <p
                              v-if="producto.precio_original && producto.precio_original > producto.precio"
                              class="mt-1 text-sm text-white/50 line-through"
                            >
                              {{ formatPrice(producto.precio_original) }}
                            </p>
                          </div>

                          <div class="grid gap-3 sm:grid-cols-1">
                            <div class="rounded-[22px] border border-white/10 bg-white/10 p-4">
                              <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/45">
                                Categoría
                              </p>
                              <p class="mt-2 text-sm font-semibold">
                                {{ producto.categoria?.nombre || 'Sin categoría' }}
                              </p>
                            </div>

                            <!--<div class="rounded-[22px] border border-white/10 bg-white/5 p-4">
                              <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/45">
                                Marca
                              </p>
                              <p class="mt-2 text-sm font-semibold">
                                {{ producto.marca?.nombre || 'Sin marca' }}
                              </p>
                            </div>-->
                          </div>

                          <div class="rounded-[22px] border border-white/10 bg-white/10 p-4">
                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/45">
                              Descripción
                            </p>
                            <p class="mt-2 text-sm leading-6 text-white/80">
                              {{ producto.descripcion || 'Producto disponible para compra inmediata dentro del catálogo.' }}
                            </p>
                          </div>

                          <div
                            v-if="producto.tiene_oferta && producto.oferta"
                            class="rounded-[22px] border border-emerald-400/20 bg-emerald-400/10 p-4"
                          >
                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-200/70">
                              Promoción
                            </p>
                            <p class="mt-2 text-base font-black text-emerald-200">
                              {{ producto.oferta.nombre }}
                            </p>
                            <p class="mt-1 text-sm text-emerald-100/80">
                              {{ producto.oferta.tipo === 'porcentaje' ? `${producto.oferta.valor}% de descuento` : `${formatPrice(producto.oferta.valor)} de descuento` }}
                            </p>
                          </div>
                        </div>

                        <div class="mt-auto grid grid-cols-2 gap-3 pt-6">
                          <Link
                            :href="`/productos/${producto.slug}`"
                            class="inline-flex h-12 items-center justify-center rounded-full border border-white/30 bg-white/10 px-4 text-sm font-black text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-white hover:text-[#062A5E]"
                          >
                            Ver detalle
                          </Link>

                          <button
                            type="button"
                            class="relative inline-flex h-12 items-center justify-center rounded-full bg-[linear-gradient(135deg,#ffffff_0%,#dff7ff_100%)] px-4 text-sm font-black text-[#062A5E] transition-all duration-300 hover:-translate-y-0.5 hover:bg-[linear-gradient(135deg,#30BEEF_0%,#ffffff_100%)] hover:text-[#062A5E] hover:shadow-[0_14px_30px_rgba(255,255,255,0.20)] disabled:cursor-not-allowed disabled:bg-white/20 disabled:text-white/50"
                            :disabled="producto.stock < 1"
                            @click="addToCart(producto)"
                          >
                            Agregar
                          </button>
                        </div>
                      </div>
                    </article>
                  </div>
                </div>
              </div>

              <div
                v-else
                class="rounded-[30px] border border-neutral-200 bg-white px-6 py-14 text-center shadow-[0_18px_45px_rgba(17,20,44,0.06)]"
              >
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-[var(--brand-blue)]/10 text-[var(--brand-blue)]">
                  <svg viewBox="0 0 24 24" class="h-9 w-9 fill-none stroke-current" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" />
                  </svg>
                </div>

                <h3 class="mt-5 text-2xl font-black text-neutral-900">
                  No encontramos productos
                </h3>

                <p class="mt-2 text-neutral-500">
                  Prueba con otra búsqueda o cambia los filtros del catálogo.
                </p>

                <button
                  type="button"
                  class="mt-6 inline-flex items-center justify-center rounded-full bg-[#11142C] px-6 py-3 text-sm font-black text-white transition hover:bg-[var(--brand-green)] hover:text-[#11142C]"
                  @click="resetFilters"
                >
                  Restablecer filtros
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>

    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="translate-y-3 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="translate-y-3 opacity-0"
    >
      <div
        v-if="toast.show"
        class="fixed bottom-4 right-4 z-50 flex items-center gap-3 rounded-2xl border border-white/60 bg-white px-4 py-3 shadow-[0_16px_40px_rgba(17,20,44,0.15)]"
      >
        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[var(--brand-green)]/14 text-[var(--brand-green)]">
          <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
          </svg>
        </div>
        <div>
          <p class="text-sm font-black text-neutral-900">Carrito actualizado</p>
          <p class="text-sm text-neutral-500">{{ toast.text }}</p>
        </div>
      </div>
    </Transition>
  </PublicLayout>
</template>

<style scoped>
.bubble {
  position: absolute;
  left: 50%;
  top: 50%;
  height: 10px;
  width: 10px;
  margin-left: -5px;
  margin-top: -5px;
  border-radius: 9999px;
  opacity: 0;
  animation: bubble-pop 0.75s ease-out forwards;
  background: radial-gradient(circle, rgba(48,190,239,0.9) 0%, rgba(125,208,60,0.95) 100%);
}

.bubble-1 { --tx: -24px; --ty: -34px; animation-delay: 0s; }
.bubble-2 { --tx: 28px; --ty: -30px; animation-delay: 0.04s; }
.bubble-3 { --tx: -30px; --ty: 8px; animation-delay: 0.08s; }
.bubble-4 { --tx: 34px; --ty: 10px; animation-delay: 0.12s; }
.bubble-5 { --tx: 0px; --ty: -42px; animation-delay: 0.16s; }

@keyframes bubble-pop {
  0% {
    transform: translate(0, 0) scale(0.35);
    opacity: 0;
  }
  18% {
    opacity: 1;
  }
  100% {
    transform: translate(var(--tx), var(--ty)) scale(1.05);
    opacity: 0;
  }
}
</style>
