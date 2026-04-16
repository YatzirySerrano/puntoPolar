<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
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

interface Marca {
  id: number
  nombre: string
  slug?: string | null
  logo?: string | null
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
  marca?: {
    id: number
    nombre: string
    slug?: string
    logo?: string | null
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
  productos?: Producto[]
  marcas?: Marca[]
  banners?: Banner[]
}>()

const currentSlide = ref(0)
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
        'Explora categorías, revisa productos destacados y entra al catálogo completo.',
      cta: 'Ver productos',
      href: '/productos',
    }))

  if (dbSlides.length > 0) return dbSlides

  return [
    {
      id: 0,
      image: heroImg,
      badge: 'Catálogo',
      title: 'Descubre productos con una experiencia más elegante',
      subtitle:
        'Un catálogo visual, rápido y claro para explorar categorías, comparar opciones y comprar mejor.',
      cta: 'Ver productos',
      href: '/productos',
    },
  ]
})

const categoriesWithCount = computed(() => {
  const source = props.productos?.length ? props.productos : props.destacados ?? []

  return (props.categorias ?? []).map((categoria) => {
    const count = source.filter((producto) => {
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

const featuredBrands = computed(() => {
  if (props.marcas?.length) {
    return props.marcas.slice(0, 8)
  }

  const map = new Map<number, Marca>()
  const source = [
    ...(props.productos ?? []),
    ...(props.destacados ?? []),
  ]

  for (const producto of source) {
    if (producto.marca?.id && producto.marca?.nombre) {
      map.set(producto.marca.id, {
        id: producto.marca.id,
        nombre: producto.marca.nombre,
        slug: producto.marca.slug ?? null,
        logo: producto.marca.logo ?? null,
      })
    }
  }

  return Array.from(map.values())
    .sort((a, b) => a.nombre.localeCompare(b.nombre))
    .slice(0, 8)
})

let autoSlideTimer: number | null = null

function startAutoSlide() {
  stopAutoSlide()

  if (slides.value.length <= 1) return

  autoSlideTimer = window.setInterval(() => {
    nextSlide()
  }, 5000)
}

function stopAutoSlide() {
  if (autoSlideTimer) {
    window.clearInterval(autoSlideTimer)
    autoSlideTimer = null
  }
}

onMounted(() => {
  startAutoSlide()
})

onBeforeUnmount(() => {
  stopAutoSlide()
})

function goToSlide(index: number) {
  currentSlide.value = index
  startAutoSlide()
}

function nextSlide() {
  if (!slides.value.length) return
  currentSlide.value = (currentSlide.value + 1) % slides.value.length
}

function prevSlide() {
  if (!slides.value.length) return
  currentSlide.value = (currentSlide.value - 1 + slides.value.length) % slides.value.length
}

function formatPrice(value: number | string | null | undefined) {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN',
    maximumFractionDigits: 2,
  }).format(Number(value ?? 0))
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

    <div class="bg-[linear-gradient(180deg,#ffffff_0%,#f7fbff_100%)]">
      <section class="relative overflow-hidden px-4 pb-12 pt-6 md:px-8 md:pt-8">
        <div class="mx-auto w-full max-w-[1500px]">
          <div class="overflow-hidden rounded-[36px] border border-neutral-200 bg-white shadow-[0_28px_80px_rgba(17,20,44,0.10)]">
            <div class="relative min-h-[440px] overflow-hidden md:min-h-[600px]">
              <img
                :src="slides[currentSlide].image"
                :alt="slides[currentSlide].title"
                class="absolute inset-0 h-full w-full object-cover transition-all duration-700"
              />

              <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(17,20,44,0.90)_0%,rgba(17,20,44,0.58)_38%,rgba(17,20,44,0.10)_100%)]" />

              <img
                :src="decoDots"
                alt=""
                class="pointer-events-none absolute right-0 top-0 hidden h-full w-full object-cover opacity-10 md:block"
              />

              <div class="relative z-10 flex min-h-[440px] flex-col justify-between p-6 md:min-h-[600px] md:p-12">
                <div class="max-w-[780px]">
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
                    <Link
                      :href="slides[currentSlide].href"
                      class="inline-flex items-center gap-2 rounded-full bg-[var(--brand-green)] px-7 py-3.5 text-sm font-black text-[#11142C] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_14px_30px_rgba(125,208,60,0.28)]"
                    >
                      {{ slides[currentSlide].cta }}
                    </Link>

                    <a
                      href="#catalogo-categorias"
                      class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-7 py-3.5 text-sm font-black text-white backdrop-blur transition-all duration-300 hover:bg-white hover:text-[#11142C]"
                    >
                      Ver categorías
                    </a>
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
                      ‹
                    </button>

                    <button
                      type="button"
                      class="inline-flex h-12 w-12 items-center justify-center rounded-full border border-white/20 bg-white text-[#11142C] transition-all duration-300 hover:scale-[1.04] hover:bg-[var(--brand-green)]"
                      @click="nextSlide"
                    >
                      ›
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
                Encuentra rápido la sección que buscas y entra directo al catálogo completo.
              </p>
            </div>

            <Link
              href="/productos"
              class="inline-flex items-center gap-2 rounded-full border border-neutral-200 bg-white px-6 py-3 text-sm font-black text-neutral-700 transition-all duration-300 hover:-translate-y-0.5 hover:border-[var(--brand-blue)] hover:bg-[var(--brand-blue)]/8 hover:text-[var(--brand-blue)]"
            >
              Ir al catálogo
            </Link>
          </div>

          <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <article
              v-for="categoria in categoriesWithCount"
              :key="categoria.id"
              class="group overflow-hidden rounded-[30px] border border-neutral-200 bg-white shadow-[0_18px_45px_rgba(17,20,44,0.06)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_24px_55px_rgba(17,20,44,0.10)]"
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
                      Disponible en catálogo
                    </p>
                  </div>

                  <Link
                    href="/productos"
                    class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-[#11142C] transition-all duration-300 group-hover:translate-x-1 group-hover:bg-[var(--brand-green)]"
                  >
                    →
                  </Link>
                </div>
              </div>
            </article>
          </div>
        </div>
      </section>

      <section id="marcas-destacadas" class="px-4 py-14 md:px-8">
        <div class="mx-auto w-full max-w-[1500px]">
          <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
              <span class="inline-flex rounded-full bg-[var(--brand-green)]/12 px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-[var(--brand-green)]">
                Marcas
              </span>
              <h2 class="mt-4 text-4xl font-black tracking-tight text-neutral-900 md:text-5xl">
                Marcas disponibles
              </h2>
              <p class="mt-3 max-w-2xl text-base leading-7 text-neutral-600">
                Revisa algunas de las marcas que forman parte del catálogo actual.
              </p>
            </div>

            <Link
              href="/productos"
              class="inline-flex items-center gap-2 rounded-full border border-neutral-200 bg-white px-6 py-3 text-sm font-black text-neutral-700 transition-all duration-300 hover:-translate-y-0.5 hover:border-[var(--brand-blue)] hover:bg-[var(--brand-blue)]/8 hover:text-[var(--brand-blue)]"
            >
              Ver catálogo
            </Link>
          </div>

          <div
            v-if="featuredBrands.length"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6"
          >
            <article
              v-for="marca in featuredBrands"
              :key="marca.id"
              class="group rounded-[28px] border border-neutral-200 bg-white p-5 text-center shadow-[0_16px_35px_rgba(17,20,44,0.05)] transition-all duration-300 hover:-translate-y-1 hover:border-[var(--brand-blue)]/25 hover:shadow-[0_24px_45px_rgba(17,20,44,0.08)]"
            >
              <div class="mx-auto flex h-20 w-20 items-center justify-center overflow-hidden rounded-[24px] bg-[var(--brand-soft)] shadow-inner">
                <img
                  v-if="marca.logo"
                  :src="marca.logo"
                  :alt="marca.nombre"
                  class="h-full w-full object-contain p-3 transition-transform duration-300 group-hover:scale-[1.06]"
                />
                <span
                  v-else
                  class="text-xl font-black uppercase tracking-[0.12em] text-neutral-700"
                >
                  {{ marca.nombre.slice(0, 2) }}
                </span>
              </div>

              <h3 class="mt-4 line-clamp-2 text-base font-black text-neutral-900">
                {{ marca.nombre }}
              </h3>

              <p class="mt-1 text-sm text-neutral-500">
                Disponible en catálogo
              </p>

              <Link
                href="/productos"
                class="mt-4 inline-flex items-center justify-center rounded-full border border-neutral-200 px-4 py-2 text-sm font-black text-neutral-700 transition-all duration-300 hover:border-[var(--brand-blue)] hover:bg-[var(--brand-blue)]/8 hover:text-[var(--brand-blue)]"
              >
                Ver productos
              </Link>
            </article>
          </div>

          <div
            v-else
            class="rounded-[30px] border border-neutral-200 bg-white px-6 py-12 text-center shadow-[0_18px_45px_rgba(17,20,44,0.06)]"
          >
            <h3 class="text-2xl font-black text-neutral-900">
              Aún no se pudieron cargar marcas aquí
            </h3>
            <p class="mt-2 text-neutral-500">
              El home necesita recibir marcas o productos desde backend para llenar esta sección.
            </p>
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
              <p class="mt-3 max-w-2xl text-base leading-7 text-neutral-600">
                Una selección visual de productos destacados para entrar directo a ver detalles o agregar al carrito.
              </p>
            </div>

            <Link
              href="/productos"
              class="inline-flex items-center gap-2 rounded-full bg-[#11142C] px-6 py-3 text-sm font-black text-white transition-all duration-300 hover:bg-[var(--brand-green)] hover:text-[#11142C]"
            >
              Ver todos los productos
            </Link>
          </div>

          <div v-if="featuredProducts.length" class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            <article
              v-for="producto in featuredProducts"
              :key="producto.id"
              class="group overflow-hidden rounded-[30px] border border-white/70 bg-white shadow-[0_18px_45px_rgba(17,20,44,0.06)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_24px_55px_rgba(17,20,44,0.10)]"
            >
              <div class="relative h-[260px] overflow-hidden bg-[var(--brand-soft)]">
                <img
                  :src="producto.imagen_principal || heroImg"
                  :alt="producto.nombre"
                  class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.05]"
                />

                <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(17,20,44,0.02)_0%,rgba(17,20,44,0.28)_100%)]" />

                <div class="absolute left-4 top-4 flex flex-wrap gap-2">
                  <span class="rounded-full bg-white/92 px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-[#11142C]">
                    Destacado
                  </span>

                  <span
                    v-if="producto.tiene_oferta"
                    class="rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-emerald-700"
                  >
                    Oferta
                  </span>
                </div>

                <div class="absolute right-4 top-4 rounded-2xl border border-white/30 bg-white/15 px-3 py-2 text-[11px] font-black uppercase tracking-[0.14em] text-white backdrop-blur">
                  {{ producto.stock > 0 ? `${producto.stock} stock` : 'Sin stock' }}
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

                <div class="mt-4">
                  <p
                    v-if="producto.precio_original && producto.precio_original > producto.precio"
                    class="text-sm text-neutral-400 line-through"
                  >
                    {{ formatPrice(producto.precio_original) }}
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

                <div class="mt-5 grid grid-cols-2 gap-3">
                  <Link
                    :href="`/productos/${producto.slug}`"
                    class="inline-flex h-12 items-center justify-center rounded-full border border-[var(--brand-blue)] bg-white px-4 text-sm font-black text-[var(--brand-blue)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-[var(--brand-blue)] hover:text-white hover:shadow-sm"
                  >
                    Ver detalle
                  </Link>

                  <button
                    type="button"
                    class="relative inline-flex h-12 items-center justify-center rounded-full bg-[#11142C] px-4 text-sm font-black text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-[var(--brand-green)] hover:text-[#11142C] disabled:cursor-not-allowed disabled:bg-neutral-300 disabled:text-white"
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
                <path stroke-linecap="round" stroke-linejoin="round" d="m12 5v14M5 12h14" />
              </svg>
            </div>

            <h3 class="mt-5 text-2xl font-black text-neutral-900">
              Aún no hay productos destacados
            </h3>

            <p class="mt-2 text-neutral-500">
              Puedes revisar todo el catálogo desde la página de productos.
            </p>

            <Link
              href="/productos"
              class="mt-6 inline-flex items-center justify-center rounded-full bg-[#11142C] px-6 py-3 text-sm font-black text-white transition hover:bg-[var(--brand-green)] hover:text-[#11142C]"
            >
              Ver catálogo
            </Link>
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
          ✓
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
