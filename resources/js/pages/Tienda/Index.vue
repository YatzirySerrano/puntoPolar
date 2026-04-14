<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import PublicLayout from '@/layouts/PublicLayout.vue'
import heroImg from '@/img/hero-tienda.jpeg'
import decoDots from '@/img/web-recurso-2.png'

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
  categoria_id?: number | null
  categoria?: Categoria | null
  marca?: {
    id: number
    nombre: string
    slug?: string
  } | null
}

interface Banner {
  id: number
  titulo: string
  descripcion?: string | null
  imagen: string | null
  url?: string | null
  orden?: number | null
}

interface HeroSlide {
  id: number
  image: string
  badge: string
  title: string
  subtitle: string
  cta: string
  href: string
}

const props = defineProps<{
  categorias: Categoria[]
  destacados: Producto[]
  productos: Producto[]
  banners: Banner[]
  filtros?: {
    buscar?: string | null
    categoria?: string | number | null
  }
}>()

const currentSlide = ref(0)
const selectedCategoryId = ref<number | null>(
  props.filtros?.categoria ? Number(props.filtros.categoria) : null,
)
const searchText = ref(props.filtros?.buscar ?? '')
const sortBy = ref<'relevancia' | 'precio_asc' | 'precio_desc' | 'nombre'>('relevancia')
const animatingProductId = ref<number | null>(null)
const toast = ref<{ show: boolean; text: string }>({
  show: false,
  text: '',
})

const slides = computed<HeroSlide[]>(() => {
  const dbSlides = (props.banners ?? [])
    .filter((banner) => !!banner.imagen)
    .map((banner) => ({
      id: banner.id,
      image: banner.imagen as string,
      badge: 'Promoción',
      title: banner.titulo,
      subtitle:
        banner.descripcion ||
        'Explora categorías, promociones activas y productos destacados.',
      cta: 'Ver productos',
      href: '#catalogo-productos',
    }))

  if (dbSlides.length > 0) return dbSlides

  return [
    {
      id: 0,
      image: heroImg,
      badge: 'Colección principal',
      title: 'Descubre productos con una experiencia más elegante',
      subtitle:
        'Un catálogo visual, rápido y claro para explorar categorías, comparar opciones y comprar mejor.',
      cta: 'Explorar catálogo',
      href: '#catalogo-productos',
    },
  ]
})

const categoriesWithCount = computed(() => {
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

const featuredProducts = computed(() => {
  return (props.destacados ?? []).slice(0, 8)
})

const filteredProducts = computed(() => {
  const term = searchText.value.trim().toLowerCase()

  let items = [...(props.productos ?? [])]

  if (selectedCategoryId.value) {
    items = items.filter((producto) => {
      const productCategoryId = producto.categoria?.id ?? producto.categoria_id ?? null
      return productCategoryId === selectedCategoryId.value
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
      ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase()

      return searchable.includes(term)
    })
  }

  if (sortBy.value === 'precio_asc') {
    items.sort((a, b) => Number(a.precio) - Number(b.precio))
  } else if (sortBy.value === 'precio_desc') {
    items.sort((a, b) => Number(b.precio) - Number(a.precio))
  } else if (sortBy.value === 'nombre') {
    items.sort((a, b) => a.nombre.localeCompare(b.nombre))
  }

  return items
})

const stats = computed(() => {
  return {
    categorias: props.categorias?.length ?? 0,
    destacados: props.destacados?.length ?? 0,
    productos: props.productos?.length ?? 0,
  }
})

function goToSlide(index: number) {
  currentSlide.value = index
}

function nextSlide() {
  if (!slides.value.length) return
  currentSlide.value = (currentSlide.value + 1) % slides.value.length
}

function prevSlide() {
  if (!slides.value.length) return
  currentSlide.value = (currentSlide.value - 1 + slides.value.length) % slides.value.length
}

function formatPrice(value: number | string) {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN',
    maximumFractionDigits: 2,
  }).format(Number(value))
}

function setCategory(categoryId: number | null) {
  selectedCategoryId.value = categoryId
  document.getElementById('catalogo-productos')?.scrollIntoView({ behavior: 'smooth' })
}

function resetFilters() {
  selectedCategoryId.value = null
  searchText.value = ''
  sortBy.value = 'relevancia'
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
    <Head title="Tienda" />

    <div class="bg-white">
      <section class="relative overflow-hidden px-4 pb-12 pt-6 md:px-8 md:pt-8">
        <div class="mx-auto w-full max-w-[1500px]">
          <div class="overflow-hidden rounded-[36px] border border-neutral-200 bg-white shadow-[0_28px_80px_rgba(17,20,44,0.10)]">
            <div class="relative min-h-[500px] overflow-hidden md:min-h-[660px]">
              <img
                :src="slides[currentSlide].image"
                :alt="slides[currentSlide].title"
                class="absolute inset-0 h-full w-full object-cover"
              />

              <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(17,20,44,0.90)_0%,rgba(17,20,44,0.58)_36%,rgba(17,20,44,0.08)_100%)]" />

              <img
                :src="decoDots"
                alt=""
                class="pointer-events-none absolute right-0 top-0 hidden h-full w-full object-cover opacity-10 md:block"
              />

              <div class="relative z-10 flex min-h-[500px] flex-col justify-between p-6 md:min-h-[660px] md:p-12">
                <div class="max-w-[760px]">
                  <div class="flex flex-wrap gap-3">
                    <span class="inline-flex rounded-full bg-white/14 px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-white backdrop-blur">
                      {{ slides[currentSlide].badge }}
                    </span>

                    <span class="inline-flex rounded-full bg-[var(--brand-green)] px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-[#11142C]">
                      Catálogo actualizado
                    </span>
                  </div>

                  <h1 class="mt-6 max-w-[760px] text-4xl font-black leading-[0.95] text-white md:text-7xl">
                    {{ slides[currentSlide].title }}
                  </h1>

                  <p class="mt-5 max-w-[620px] text-base leading-7 text-white/85 md:text-xl">
                    {{ slides[currentSlide].subtitle }}
                  </p>

                  <div class="mt-8 flex flex-wrap gap-3">
                    <a
                      :href="slides[currentSlide].href"
                      class="inline-flex items-center gap-2 rounded-full bg-[var(--brand-green)] px-7 py-3.5 text-sm font-black text-[#11142C] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_14px_30px_rgba(125,208,60,0.28)]"
                    >
                      {{ slides[currentSlide].cta }}
                      <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6" />
                      </svg>
                    </a>

                    <a
                      href="#catalogo-categorias"
                      class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-7 py-3.5 text-sm font-black text-white backdrop-blur transition-all duration-300 hover:bg-white hover:text-[#11142C]"
                    >
                      Ver categorías
                    </a>
                  </div>

                  <div class="mt-8 grid max-w-[620px] gap-3 sm:grid-cols-3">
                    <div class="rounded-[24px] border border-white/12 bg-white/8 p-4 backdrop-blur">
                      <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/65">Categorías</p>
                      <p class="mt-2 text-3xl font-black text-white">{{ stats.categorias }}</p>
                    </div>
                    <div class="rounded-[24px] border border-white/12 bg-white/8 p-4 backdrop-blur">
                      <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/65">Destacados</p>
                      <p class="mt-2 text-3xl font-black text-white">{{ stats.destacados }}</p>
                    </div>
                    <div class="rounded-[24px] border border-white/12 bg-white/8 p-4 backdrop-blur">
                      <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/65">Productos</p>
                      <p class="mt-2 text-3xl font-black text-white">{{ stats.productos }}</p>
                    </div>
                  </div>
                </div>

                <div class="flex flex-col gap-4 pt-8 md:flex-row md:items-center md:justify-between">
                  <div class="flex items-center gap-2">
                    <button
                      v-for="(slide, index) in slides"
                      :key="slide.id"
                      type="button"
                      class="transition-all duration-300"
                      :class="
                        currentSlide === index
                          ? 'h-3 w-10 rounded-full bg-white'
                          : 'h-3 w-3 rounded-full bg-white/45 hover:bg-white/70'
                      "
                      @click="goToSlide(index)"
                    />
                  </div>

                  <div class="flex items-center gap-3">
                    <button
                      type="button"
                      class="inline-flex h-12 w-12 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white backdrop-blur transition-all duration-300 hover:bg-white hover:text-[#11142C]"
                      @click="prevSlide"
                    >
                      <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6" />
                      </svg>
                    </button>

                    <button
                      type="button"
                      class="inline-flex h-12 w-12 items-center justify-center rounded-full border border-white/20 bg-white text-[#11142C] transition-all duration-300 hover:scale-[1.04] hover:bg-[var(--brand-green)]"
                      @click="nextSlide"
                    >
                      <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section id="catalogo-categorias" class="px-4 py-14 md:px-8">
        <div class="mx-auto w-full max-w-[1500px]">
          <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
              <span class="inline-flex rounded-full bg-[var(--brand-blue)]/10 px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-[var(--brand-blue)]">
                Categorías
              </span>
              <h2 class="mt-4 text-4xl font-black tracking-tight text-neutral-900 md:text-5xl">
                Explora por categoría
              </h2>
              <p class="mt-3 max-w-2xl text-base leading-7 text-neutral-600">
                Encuentra rápido la sección que buscas y filtra el catálogo con un solo clic.
              </p>
            </div>

            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-full border border-neutral-200 bg-white px-6 py-3 text-sm font-black text-[#11142C] shadow-sm transition-all duration-300 hover:border-[var(--brand-blue)] hover:bg-[var(--brand-blue)]/8 hover:text-[var(--brand-blue)]"
              @click="resetFilters"
            >
              Limpiar filtros
            </button>
          </div>

          <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <button
              v-for="categoria in categoriesWithCount"
              :key="categoria.id"
              type="button"
              class="group overflow-hidden rounded-[30px] border bg-white text-left shadow-[0_18px_45px_rgba(17,20,44,0.06)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_24px_55px_rgba(17,20,44,0.10)]"
              :class="selectedCategoryId === categoria.id ? 'border-[var(--brand-blue)] ring-2 ring-[var(--brand-blue)]/10' : 'border-neutral-200'"
              @click="setCategory(categoria.id)"
            >
              <div class="relative h-[260px] overflow-hidden bg-[var(--brand-soft)]">
                <img
                  :src="categoria.imagen || heroImg"
                  :alt="categoria.nombre"
                  class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.05]"
                />
                <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(17,20,44,0.04)_0%,rgba(17,20,44,0.74)_100%)]" />
                <div class="absolute left-5 top-5">
                  <span class="rounded-full bg-white/90 px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-[#11142C]">
                    {{ categoria.count }} productos
                  </span>
                </div>
                <div class="absolute inset-x-5 bottom-5 flex items-end justify-between gap-3">
                  <div>
                    <h3 class="text-2xl font-black text-white">
                      {{ categoria.nombre }}
                    </h3>
                    <p class="mt-1 text-sm text-white/80">
                      Filtrar catálogo
                    </p>
                  </div>

                  <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-[#11142C] transition-all duration-300 group-hover:translate-x-1 group-hover:bg-[var(--brand-green)]">
                    <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6" />
                    </svg>
                  </div>
                </div>
              </div>
            </button>
          </div>
        </div>
      </section>

      <section id="productos-destacados" class="bg-[var(--brand-soft)] px-4 py-14 md:px-8">
        <div class="mx-auto w-full max-w-[1500px]">
          <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
              <span class="inline-flex rounded-full bg-[var(--brand-green)]/12 px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-[var(--brand-green)]">
                Destacados
              </span>
              <h2 class="mt-4 text-4xl font-black tracking-tight text-neutral-900 md:text-5xl">
                Productos que merecen más visibilidad
              </h2>
            </div>

            <a
              href="#catalogo-productos"
              class="inline-flex items-center gap-2 rounded-full bg-[#11142C] px-6 py-3 text-sm font-black text-white transition-all duration-300 hover:bg-[var(--brand-green)] hover:text-[#11142C]"
            >
              Ver todo el catálogo
            </a>
          </div>

          <div v-if="featuredProducts.length" class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            <article
              v-for="producto in featuredProducts"
              :key="producto.id"
              class="overflow-hidden rounded-[30px] border border-white/70 bg-white shadow-[0_18px_45px_rgba(17,20,44,0.06)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_24px_55px_rgba(17,20,44,0.10)]"
            >
              <div class="relative h-[260px] overflow-hidden bg-[var(--brand-soft)]">
                <img
                  :src="producto.imagen_principal || heroImg"
                  :alt="producto.nombre"
                  class="h-full w-full object-cover transition-transform duration-500 hover:scale-[1.04]"
                />

                <div class="absolute left-4 top-4 flex flex-wrap gap-2">
                  <span class="rounded-full bg-white/92 px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-[#11142C]">
                    Destacado
                  </span>

                  <span
                    v-if="producto.stock <= 3"
                    class="rounded-full bg-amber-100 px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-amber-700"
                  >
                    Últimas piezas
                  </span>
                </div>
              </div>

              <div class="p-5">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-neutral-400">
                  {{ producto.categoria?.nombre || 'Producto' }}
                </p>

                <h3 class="mt-2 line-clamp-2 text-xl font-black text-neutral-900">
                  {{ producto.nombre }}
                </h3>

                <p class="mt-2 text-sm text-neutral-500">
                  SKU: {{ producto.sku }}
                </p>

                <div class="mt-4 flex items-end justify-between gap-3">
                  <div>
                    <p
                      v-if="producto.precio_comparacion && Number(producto.precio_comparacion) > Number(producto.precio)"
                      class="text-sm text-neutral-400 line-through"
                    >
                      {{ formatPrice(producto.precio_comparacion) }}
                    </p>
                    <p class="text-2xl font-black text-[#11142C]">
                      {{ formatPrice(producto.precio) }}
                    </p>
                  </div>

                  <div class="flex items-center gap-2">
                    <Link
                      :href="`/productos/${producto.slug}`"
                      class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-neutral-200 bg-white text-neutral-700 transition-all duration-300 hover:border-[var(--brand-blue)] hover:bg-[var(--brand-blue)]/10 hover:text-[var(--brand-blue)]"
                    >
                      <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" />
                        <circle cx="12" cy="12" r="3" />
                      </svg>
                    </Link>

                    <button
                      type="button"
                      class="relative inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-[#11142C] text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-[var(--brand-green)] hover:text-[#11142C]"
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

                      <svg viewBox="0 0 24 24" class="relative z-10 h-5 w-5 fill-none stroke-current" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h2l2.2 10.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.8L20 7H7" />
                        <circle cx="10" cy="19" r="1.5" />
                        <circle cx="17" cy="19" r="1.5" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </article>
          </div>
        </div>
      </section>

      <section id="catalogo-productos" class="px-4 py-14 md:px-8">
        <div class="mx-auto w-full max-w-[1500px]">
          <div class="mb-8">
            <span class="inline-flex rounded-full bg-[var(--brand-blue)]/10 px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-[var(--brand-blue)]">
              Catálogo completo
            </span>
            <h2 class="mt-4 text-4xl font-black tracking-tight text-neutral-900 md:text-5xl">
              Todos los productos
            </h2>
          </div>

          <div class="grid gap-8 xl:grid-cols-[280px_minmax(0,1fr)]">
            <aside class="space-y-5">
              <div class="rounded-[28px] border border-neutral-200 bg-white p-5 shadow-[0_18px_45px_rgba(17,20,44,0.06)]">
                <h3 class="text-lg font-black text-neutral-900">
                  Buscar
                </h3>

                <div class="mt-4 rounded-2xl border border-[var(--brand-gray)] bg-[#f8f8f8] px-4 py-3">
                  <input
                    v-model="searchText"
                    type="text"
                    placeholder="Nombre, SKU, descripción..."
                    class="w-full bg-transparent text-sm text-neutral-700 outline-none placeholder:text-neutral-400"
                  />
                </div>
              </div>

              <div class="rounded-[28px] border border-neutral-200 bg-white p-5 shadow-[0_18px_45px_rgba(17,20,44,0.06)]">
                <h3 class="text-lg font-black text-neutral-900">
                  Ordenar por
                </h3>

                <select
                  v-model="sortBy"
                  class="mt-4 h-12 w-full rounded-2xl border border-[var(--brand-gray)] bg-white px-4 text-sm font-semibold text-neutral-700 outline-none focus:border-[var(--brand-blue)]"
                >
                  <option value="relevancia">Relevancia</option>
                  <option value="precio_asc">Precio: menor a mayor</option>
                  <option value="precio_desc">Precio: mayor a menor</option>
                  <option value="nombre">Nombre</option>
                </select>
              </div>

              <div class="rounded-[28px] border border-neutral-200 bg-white p-5 shadow-[0_18px_45px_rgba(17,20,44,0.06)]">
                <h3 class="text-lg font-black text-neutral-900">
                  Categorías
                </h3>

                <div class="mt-4 flex flex-wrap gap-2">
                  <button
                    type="button"
                    class="rounded-full px-4 py-2 text-sm font-black transition"
                    :class="selectedCategoryId === null ? 'bg-[#11142C] text-white' : 'border border-neutral-200 bg-white text-neutral-700 hover:border-[var(--brand-blue)] hover:text-[var(--brand-blue)]'"
                    @click="setCategory(null)"
                  >
                    Todas
                  </button>

                  <button
                    v-for="categoria in categoriesWithCount"
                    :key="`filter-${categoria.id}`"
                    type="button"
                    class="rounded-full px-4 py-2 text-sm font-black transition"
                    :class="selectedCategoryId === categoria.id ? 'bg-[#11142C] text-white' : 'border border-neutral-200 bg-white text-neutral-700 hover:border-[var(--brand-blue)] hover:text-[var(--brand-blue)]'"
                    @click="setCategory(categoria.id)"
                  >
                    {{ categoria.nombre }}
                  </button>
                </div>
              </div>
            </aside>

            <div>
              <div class="mb-5 flex flex-col gap-3 rounded-[28px] border border-neutral-200 bg-white p-5 shadow-[0_18px_45px_rgba(17,20,44,0.06)] md:flex-row md:items-center md:justify-between">
                <div>
                  <p class="text-sm font-semibold text-neutral-500">
                    Mostrando
                    <span class="font-black text-neutral-900">{{ filteredProducts.length }}</span>
                    producto<span v-if="filteredProducts.length !== 1">s</span>
                  </p>

                  <p v-if="selectedCategoryId" class="mt-1 text-sm text-neutral-500">
                    Filtro activo:
                    <span class="font-bold text-neutral-900">
                      {{ categoriesWithCount.find((c) => c.id === selectedCategoryId)?.nombre }}
                    </span>
                  </p>
                </div>

                <button
                  type="button"
                  class="inline-flex items-center justify-center rounded-full border border-neutral-200 bg-white px-5 py-3 text-sm font-black text-[#11142C] transition hover:border-[var(--brand-blue)] hover:bg-[var(--brand-blue)]/8 hover:text-[var(--brand-blue)]"
                  @click="resetFilters"
                >
                  Restablecer
                </button>
              </div>

              <div v-if="filteredProducts.length" class="grid gap-5 sm:grid-cols-2 2xl:grid-cols-3">
                <article
                  v-for="producto in filteredProducts"
                  :key="producto.id"
                  class="group overflow-hidden rounded-[30px] border border-neutral-200 bg-white shadow-[0_14px_35px_rgba(17,20,44,0.05)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_22px_45px_rgba(17,20,44,0.08)]"
                >
                  <div class="relative h-[280px] overflow-hidden bg-[var(--brand-soft)]">
                    <img
                      :src="producto.imagen_principal || heroImg"
                      :alt="producto.nombre"
                      class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.05]"
                    />

                    <div class="absolute left-4 top-4 flex flex-wrap gap-2">
                      <span class="rounded-full bg-white/92 px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-[#11142C]">
                        {{ producto.categoria?.nombre || 'Producto' }}
                      </span>

                      <span
                        v-if="producto.stock < 1"
                        class="rounded-full bg-red-100 px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-red-600"
                      >
                        Agotado
                      </span>
                    </div>
                  </div>

                  <div class="p-5">
                    <div class="min-h-[64px]">
                      <h3 class="line-clamp-2 text-xl font-black text-neutral-900">
                        {{ producto.nombre }}
                      </h3>
                    </div>

                    <p class="mt-2 text-sm text-neutral-500">
                      SKU: {{ producto.sku }}
                    </p>

                    <p class="mt-2 line-clamp-2 text-sm leading-6 text-neutral-500">
                      {{ producto.descripcion || 'Producto disponible en catálogo.' }}
                    </p>

                    <div class="mt-4 flex items-end justify-between gap-3">
                      <div>
                        <p
                          v-if="producto.precio_comparacion && Number(producto.precio_comparacion) > Number(producto.precio)"
                          class="text-sm text-neutral-400 line-through"
                        >
                          {{ formatPrice(producto.precio_comparacion) }}
                        </p>
                        <p class="text-2xl font-black text-[#11142C]">
                          {{ formatPrice(producto.precio) }}
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
                        class="inline-flex items-center justify-center rounded-full border border-[var(--brand-blue)] px-4 py-3 text-sm font-black text-[var(--brand-blue)] transition hover:bg-[var(--brand-blue)] hover:text-white"
                      >
                        Ver detalle
                      </Link>

                      <button
                        type="button"
                        class="relative inline-flex items-center justify-center rounded-full bg-[#11142C] px-4 py-3 text-sm font-black text-white transition hover:-translate-y-0.5 hover:bg-[var(--brand-green)] hover:text-[#11142C] disabled:cursor-not-allowed disabled:bg-neutral-300 disabled:text-white"
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
                          {{ producto.stock < 1 ? 'Sin stock' : 'Agregar' }}
                        </span>
                      </button>
                    </div>
                  </div>
                </article>
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

      <section id="contacto-home" class="bg-[var(--brand-soft)] px-4 py-14 md:px-8">
        <div class="mx-auto w-full max-w-[1500px]">
          <div class="overflow-hidden rounded-[34px] border border-white/60 bg-white shadow-[0_18px_50px_rgba(17,20,44,0.06)]">
            <div class="grid gap-0 lg:grid-cols-[1.1fr_0.9fr]">
              <div class="p-8 md:p-10">
                <span class="inline-flex rounded-full bg-[var(--brand-green)]/12 px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-[var(--brand-green)]">
                  Atención
                </span>

                <h2 class="mt-5 text-4xl font-black tracking-tight text-neutral-900 md:text-5xl">
                  ¿Necesitas apoyo con tu compra?
                </h2>

                <p class="mt-4 max-w-2xl text-base leading-7 text-neutral-600">
                  Ponte en contacto con nosotros para resolver dudas, revisar disponibilidad o recibir ayuda con el proceso de compra.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                  <a
                    href="/contacto"
                    class="inline-flex items-center gap-2 rounded-full bg-[#11142C] px-7 py-3.5 text-sm font-black text-white transition-all duration-300 hover:bg-[var(--brand-green)] hover:text-[#11142C]"
                  >
                    Ir a contacto
                  </a>

                  <Link
                    href="/register"
                    class="inline-flex items-center gap-2 rounded-full border border-neutral-200 bg-white px-7 py-3.5 text-sm font-black text-[#11142C] transition-all duration-300 hover:border-[var(--brand-blue)] hover:bg-[var(--brand-blue)]/8 hover:text-[var(--brand-blue)]"
                  >
                    Crear cuenta
                  </Link>
                </div>
              </div>

              <div class="grid gap-0 border-l border-neutral-200 bg-white sm:grid-cols-3 lg:grid-cols-1">
                <div class="border-b border-neutral-200 p-6 lg:p-8">
                  <p class="text-sm font-semibold uppercase tracking-[0.14em] text-neutral-400">
                    Catálogo
                  </p>
                  <p class="mt-3 text-xl font-black text-neutral-900">
                    Categorías, filtros y productos destacados
                  </p>
                </div>

                <div class="border-b border-neutral-200 p-6 lg:p-8">
                  <p class="text-sm font-semibold uppercase tracking-[0.14em] text-neutral-400">
                    Compra
                  </p>
                  <p class="mt-3 text-xl font-black text-neutral-900">
                    Acceso directo a detalle y agregar al carrito
                  </p>
                </div>

                <div class="p-6 lg:p-8">
                  <p class="text-sm font-semibold uppercase tracking-[0.14em] text-neutral-400">
                    Experiencia
                  </p>
                  <p class="mt-3 text-xl font-black text-neutral-900">
                    Visual, limpia y mucho más útil para vender
                  </p>
                </div>
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
.bubble-5 { --tx: 0px; --ty: -40px; animation-delay: 0.16s; }

@keyframes bubble-pop {
  0% {
    transform: translate(0, 0) scale(0.6);
    opacity: 0;
  }
  20% {
    opacity: 1;
  }
  100% {
    transform: translate(var(--tx), var(--ty)) scale(1.45);
    opacity: 0;
  }
}
</style>
