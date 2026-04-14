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
                'Descubre promociones, categorías y productos destacados.',
            cta: 'Ver colección',
            href: banner.url || '#categorias',
        }))

    if (dbSlides.length > 0) return dbSlides

    return [
        {
            id: 0,
            image: heroImg,
            badge: 'Colección principal',
            title: 'Descubre productos que elevan tu cocina',
            subtitle:
                'Explora categorías, promociones y artículos destacados con una experiencia moderna y visual.',
            cta: 'Ver categorías',
            href: '#categorias',
        },
    ]
})

const featuredProducts = computed(() => {
    return (props.destacados ?? []).slice(0, 4)
})

const categoryShowcase = computed(() => {
    return (props.categorias ?? []).slice(0, 8)
})

const promoBlocks = computed(() => {
    const source = (props.categorias ?? []).slice(0, 3)

    return source.map((categoria, index) => ({
        id: categoria.id,
        title: categoria.nombre,
        subtitle: [
            'Promociones y productos seleccionados',
            'Novedades y oportunidades de compra',
            'Explora una categoría con estilo y utilidad',
        ][index] || 'Explora la categoría',
        href: `/categorias/${categoria.slug}`,
        image: categoria.imagen || heroImg,
    }))
})

const categorySections = computed(() => {
    return (props.categorias ?? [])
        .map((categoria) => {
            const items = (props.productos ?? [])
                .filter(
                    (producto) =>
                        producto.categoria?.id === categoria.id ||
                        producto.categoria_id === categoria.id
                )
                .slice(0, 4)

            return {
                ...categoria,
                productos: items,
            }
        })
        .filter((categoria) => categoria.productos.length > 0)
        .slice(0, 3)
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
    currentSlide.value =
        (currentSlide.value - 1 + slides.value.length) % slides.value.length
}

function formatPrice(value: number | string) {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        maximumFractionDigits: 2,
    }).format(Number(value))
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
        }
    )
}
</script>

<template>
    <PublicLayout>
        <Head title="Inicio" />

        <div class="bg-white">
            <section class="relative overflow-hidden px-4 pb-14 pt-6 md:px-8 md:pt-8">
                <div class="mx-auto w-full max-w-[1500px]">
                    <div
                        class="overflow-hidden rounded-[36px] border border-neutral-200 bg-white shadow-[0_28px_80px_rgba(17,20,44,0.10)]"
                    >
                        <div class="relative min-h-[460px] overflow-hidden md:min-h-[640px]">
                            <img
                                :src="slides[currentSlide].image"
                                :alt="slides[currentSlide].title"
                                class="absolute inset-0 h-full w-full object-cover"
                            />

                            <div
                                class="absolute inset-0 bg-[linear-gradient(90deg,rgba(17,20,44,0.88)_0%,rgba(17,20,44,0.60)_34%,rgba(17,20,44,0.10)_100%)]"
                            />

                            <img
                                :src="decoDots"
                                alt=""
                                class="pointer-events-none absolute right-0 top-0 hidden h-full w-full object-cover opacity-10 md:block"
                            />

                            <div
                                class="relative z-10 flex min-h-[460px] flex-col justify-between p-6 md:min-h-[640px] md:p-12"
                            >
                                <div class="max-w-[680px]">
                                    <div class="flex flex-wrap gap-3">
                                        <span
                                            class="inline-flex rounded-full bg-white/14 px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-white backdrop-blur"
                                        >
                                            {{ slides[currentSlide].badge }}
                                        </span>

                                        <span
                                            class="inline-flex rounded-full bg-[var(--brand-green)] px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-[#11142C]"
                                        >
                                            Promociones activas
                                        </span>
                                    </div>

                                    <h1
                                        class="mt-6 max-w-[700px] text-4xl font-black leading-[0.95] text-white md:text-7xl"
                                    >
                                        {{ slides[currentSlide].title }}
                                    </h1>

                                    <p
                                        class="mt-5 max-w-[580px] text-base leading-7 text-white/85 md:text-xl"
                                    >
                                        {{ slides[currentSlide].subtitle }}
                                    </p>

                                    <div class="mt-8 flex flex-wrap gap-3">
                                        <a
                                            :href="slides[currentSlide].href"
                                            class="inline-flex items-center gap-2 rounded-full bg-[var(--brand-green)] px-7 py-3.5 text-sm font-black text-[#11142C] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_14px_30px_rgba(125,208,60,0.28)]"
                                        >
                                            {{ slides[currentSlide].cta }}
                                            <svg
                                                viewBox="0 0 24 24"
                                                class="h-5 w-5 fill-none stroke-current"
                                                stroke-width="1.8"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M5 12h14M13 6l6 6-6 6"
                                                />
                                            </svg>
                                        </a>

                                        <a
                                            href="#categorias"
                                            class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-7 py-3.5 text-sm font-black text-white backdrop-blur transition-all duration-300 hover:bg-white hover:text-[#11142C]"
                                        >
                                            Explorar categorías
                                        </a>
                                    </div>

                                    <div class="mt-8 flex flex-wrap gap-2">
                                        <span
                                            class="rounded-full border border-white/15 bg-white/8 px-4 py-2 text-xs font-semibold text-white/85 backdrop-blur"
                                        >
                                            Compra por categoría
                                        </span>
                                        <span
                                            class="rounded-full border border-white/15 bg-white/8 px-4 py-2 text-xs font-semibold text-white/85 backdrop-blur"
                                        >
                                            Promociones destacadas
                                        </span>
                                        <span
                                            class="rounded-full border border-white/15 bg-white/8 px-4 py-2 text-xs font-semibold text-white/85 backdrop-blur"
                                        >
                                            Catálogo visual
                                        </span>
                                    </div>
                                </div>

                                <div
                                    class="flex flex-col gap-4 pt-8 md:flex-row md:items-center md:justify-between"
                                >
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
                                            <svg
                                                viewBox="0 0 24 24"
                                                class="h-5 w-5 fill-none stroke-current"
                                                stroke-width="1.8"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="m15 18-6-6 6-6"
                                                />
                                            </svg>
                                        </button>

                                        <button
                                            type="button"
                                            class="inline-flex h-12 w-12 items-center justify-center rounded-full border border-white/20 bg-white text-[#11142C] transition-all duration-300 hover:scale-[1.04] hover:bg-[var(--brand-green)]"
                                            @click="nextSlide"
                                        >
                                            <svg
                                                viewBox="0 0 24 24"
                                                class="h-5 w-5 fill-none stroke-current"
                                                stroke-width="1.8"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="m9 18 6-6-6-6"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="categorias" class="px-4 py-14 md:px-8">
                <div class="mx-auto w-full max-w-[1500px]">
                    <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                        <div>
                            <span
                                class="inline-flex rounded-full bg-[var(--brand-blue)]/10 px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-[var(--brand-blue)]"
                            >
                                Compra por categoría
                            </span>
                            <h2 class="mt-4 text-4xl font-black tracking-tight text-neutral-900 md:text-5xl">
                                Encuentra rápido lo que buscas
                            </h2>
                            <p class="mt-3 max-w-2xl text-base leading-7 text-neutral-600">
                                Un índice claro, visual y moderno para entrar directo a las secciones principales.
                            </p>
                        </div>

                        <Link
                            href="/register"
                            class="inline-flex items-center gap-2 rounded-full bg-[#11142C] px-6 py-3 text-sm font-black text-white transition-all duration-300 hover:bg-[var(--brand-green)] hover:text-[#11142C]"
                        >
                            Crear cuenta
                            <svg
                                viewBox="0 0 24 24"
                                class="h-5 w-5 fill-none stroke-current"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M5 12h14M13 6l6 6-6 6"
                                />
                            </svg>
                        </Link>
                    </div>

                    <div
                        v-if="categoryShowcase.length"
                        class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4"
                    >
                        <Link
                            v-for="categoria in categoryShowcase"
                            :key="categoria.id"
                            :href="`/categorias/${categoria.slug}`"
                            class="group overflow-hidden rounded-[30px] border border-neutral-200 bg-white shadow-[0_18px_45px_rgba(17,20,44,0.06)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_24px_55px_rgba(17,20,44,0.10)]"
                        >
                            <div class="relative h-[280px] overflow-hidden bg-[var(--brand-soft)]">
                                <img
                                    :src="categoria.imagen || heroImg"
                                    :alt="categoria.nombre"
                                    class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.05]"
                                />
                                <div
                                    class="absolute inset-0 bg-[linear-gradient(180deg,rgba(17,20,44,0.04)_0%,rgba(17,20,44,0.70)_100%)]"
                                />
                                <div class="absolute left-5 top-5">
                                    <span
                                        class="rounded-full bg-white/90 px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-[#11142C]"
                                    >
                                        Categoría
                                    </span>
                                </div>
                                <div
                                    class="absolute inset-x-5 bottom-5 flex items-end justify-between gap-3"
                                >
                                    <h3 class="text-2xl font-black text-white">
                                        {{ categoria.nombre }}
                                    </h3>

                                    <div
                                        class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-[#11142C] transition-all duration-300 group-hover:translate-x-1 group-hover:bg-[var(--brand-green)]"
                                    >
                                        <svg
                                            viewBox="0 0 24 24"
                                            class="h-5 w-5 fill-none stroke-current"
                                            stroke-width="1.8"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 12h14M13 6l6 6-6 6"
                                            />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <div
                        v-if="categoryShowcase.length"
                        class="mt-6 flex flex-wrap gap-3"
                    >
                        <Link
                            v-for="categoria in categoryShowcase"
                            :key="`pill-${categoria.id}`"
                            :href="`/categorias/${categoria.slug}`"
                            class="rounded-full border border-neutral-200 bg-white px-5 py-3 text-sm font-bold text-neutral-700 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-[var(--brand-blue)] hover:bg-[var(--brand-blue)]/8 hover:text-[var(--brand-blue)]"
                        >
                            {{ categoria.nombre }}
                        </Link>
                    </div>
                </div>
            </section>

            <section class="bg-[var(--brand-soft)] px-4 py-14 md:px-8">
                <div class="mx-auto w-full max-w-[1500px]">
                    <div class="mb-8">
                        <span
                            class="inline-flex rounded-full bg-[var(--brand-green)]/12 px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-[var(--brand-green)]"
                        >
                            Promociones y oportunidades
                        </span>
                        <h2 class="mt-4 text-4xl font-black tracking-tight text-neutral-900 md:text-5xl">
                            Secciones pensadas para vender mejor
                        </h2>
                    </div>

                    <div
                        v-if="promoBlocks.length"
                        class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr_0.8fr]"
                    >
                        <Link
                            v-if="promoBlocks[0]"
                            :href="promoBlocks[0].href"
                            class="group overflow-hidden rounded-[34px] border border-white/60 bg-white shadow-[0_18px_50px_rgba(17,20,44,0.06)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_24px_60px_rgba(17,20,44,0.10)]"
                        >
                            <div class="relative h-[420px] overflow-hidden">
                                <img
                                    :src="promoBlocks[0].image"
                                    :alt="promoBlocks[0].title"
                                    class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.04]"
                                />
                                <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(17,20,44,0.12)_0%,rgba(17,20,44,0.78)_100%)]" />
                                <div class="absolute inset-x-6 bottom-6">
                                    <span
                                        class="rounded-full bg-[var(--brand-green)] px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-[#11142C]"
                                    >
                                        {{ promoBlocks[0].subtitle }}
                                    </span>
                                    <h3 class="mt-4 text-4xl font-black text-white">
                                        {{ promoBlocks[0].title }}
                                    </h3>
                                </div>
                            </div>
                        </Link>

                        <div class="grid gap-6">
                            <Link
                                v-for="block in promoBlocks.slice(1)"
                                :key="block.id"
                                :href="block.href"
                                class="group overflow-hidden rounded-[30px] border border-white/60 bg-white shadow-[0_18px_45px_rgba(17,20,44,0.06)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_24px_55px_rgba(17,20,44,0.10)]"
                            >
                                <div class="relative h-[197px] overflow-hidden">
                                    <img
                                        :src="block.image"
                                        :alt="block.title"
                                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.05]"
                                    />
                                    <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(17,20,44,0.08)_0%,rgba(17,20,44,0.72)_100%)]" />
                                    <div class="absolute inset-x-5 bottom-5">
                                        <p class="text-sm font-semibold text-white/80">
                                            {{ block.subtitle }}
                                        </p>
                                        <h3 class="mt-2 text-2xl font-black text-white">
                                            {{ block.title }}
                                        </h3>
                                    </div>
                                </div>
                            </Link>
                        </div>

                        <div class="grid gap-6">
                            <div
                                class="rounded-[30px] border border-white/60 bg-white p-6 shadow-[0_18px_45px_rgba(17,20,44,0.06)]"
                            >
                                <span
                                    class="inline-flex rounded-full bg-[var(--brand-blue)]/10 px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-[var(--brand-blue)]"
                                >
                                    Beneficio
                                </span>
                                <h3 class="mt-5 text-3xl font-black text-neutral-900">
                                    Un inicio más limpio y más visual
                                </h3>
                                <p class="mt-4 leading-7 text-neutral-500">
                                    Aquí la idea es que el usuario vea promociones, categorías y puntos de entrada sin sentirse saturado.
                                </p>
                            </div>

                            <div
                                class="rounded-[30px] border border-white/60 bg-[#11142C] p-6 text-white shadow-[0_18px_45px_rgba(17,20,44,0.16)]"
                            >
                                <span
                                    class="inline-flex rounded-full bg-white/10 px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-white"
                                >
                                    Acción rápida
                                </span>
                                <h3 class="mt-5 text-3xl font-black">
                                    Crea tu cuenta y compra más rápido
                                </h3>
                                <p class="mt-4 leading-7 text-white/75">
                                    Guarda productos, revisa promociones y da seguimiento más fácil a tu compra.
                                </p>
                                <Link
                                    href="/register"
                                    class="mt-6 inline-flex items-center gap-2 rounded-full bg-[var(--brand-green)] px-6 py-3 text-sm font-black text-[#11142C] transition-all duration-300 hover:-translate-y-0.5"
                                >
                                    Crear cuenta
                                    <svg
                                        viewBox="0 0 24 24"
                                        class="h-5 w-5 fill-none stroke-current"
                                        stroke-width="1.8"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M5 12h14M13 6l6 6-6 6"
                                        />
                                    </svg>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="px-4 py-14 md:px-8">
                <div class="mx-auto w-full max-w-[1500px]">
                    <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                        <div>
                            <span
                                class="inline-flex rounded-full bg-[var(--brand-blue)]/10 px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-[var(--brand-blue)]"
                            >
                                Selección breve
                            </span>
                            <h2 class="mt-4 text-4xl font-black tracking-tight text-neutral-900 md:text-5xl">
                                Productos destacados
                            </h2>
                        </div>

                        <a
                            href="#contacto-home"
                            class="inline-flex items-center gap-2 rounded-full border border-neutral-200 bg-white px-6 py-3 text-sm font-black text-[#11142C] shadow-sm transition-all duration-300 hover:border-[var(--brand-blue)] hover:bg-[var(--brand-blue)]/8 hover:text-[var(--brand-blue)]"
                        >
                            Ir a contacto
                        </a>
                    </div>

                    <div
                        v-if="featuredProducts.length"
                        class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4"
                    >
                        <article
                            v-for="producto in featuredProducts"
                            :key="producto.id"
                            class="overflow-hidden rounded-[28px] border border-neutral-200 bg-white shadow-[0_14px_35px_rgba(17,20,44,0.05)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_22px_45px_rgba(17,20,44,0.08)]"
                        >
                            <div class="relative h-[250px] overflow-hidden bg-[var(--brand-soft)]">
                                <img
                                    :src="producto.imagen_principal || heroImg"
                                    :alt="producto.nombre"
                                    class="h-full w-full object-cover transition-transform duration-500 hover:scale-[1.04]"
                                />
                            </div>

                            <div class="p-5">
                                <h3 class="line-clamp-2 text-xl font-black text-neutral-900">
                                    {{ producto.nombre }}
                                </h3>

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
                                            <svg
                                                viewBox="0 0 24 24"
                                                class="h-5 w-5 fill-none stroke-current"
                                                stroke-width="1.8"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"
                                                />
                                                <circle cx="12" cy="12" r="3" />
                                            </svg>
                                        </Link>

                                        <button
                                            type="button"
                                            class="relative inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-[#11142C] text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-[var(--brand-green)] hover:text-[#11142C]"
                                            @click="addToCart(producto)"
                                        >
                                            <span
                                                v-if="animatingProductId === producto.id"
                                                class="pointer-events-none absolute inset-0"
                                            >
                                                <span class="bubble bubble-1" />
                                                <span class="bubble bubble-2" />
                                                <span class="bubble bubble-3" />
                                                <span class="bubble bubble-4" />
                                                <span class="bubble bubble-5" />
                                            </span>

                                            <svg
                                                viewBox="0 0 24 24"
                                                class="relative z-10 h-5 w-5 fill-none stroke-current"
                                                stroke-width="1.8"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M3 4h2l2.2 10.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.8L20 7H7"
                                                />
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

            <section id="contacto-home" class="bg-[var(--brand-soft)] px-4 py-14 md:px-8">
                <div class="mx-auto w-full max-w-[1500px]">
                    <div
                        class="overflow-hidden rounded-[34px] border border-white/60 bg-white shadow-[0_18px_50px_rgba(17,20,44,0.06)]"
                    >
                        <div class="grid gap-0 lg:grid-cols-[1.1fr_0.9fr]">
                            <div class="p-8 md:p-10">
                                <span
                                    class="inline-flex rounded-full bg-[var(--brand-green)]/12 px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-[var(--brand-green)]"
                                >
                                    Contacto
                                </span>

                                <h2 class="mt-5 text-4xl font-black tracking-tight text-neutral-900 md:text-5xl">
                                    ¿Necesitas ayuda o atención personalizada?
                                </h2>

                                <p class="mt-4 max-w-2xl text-base leading-7 text-neutral-600">
                                    Ponte en contacto con nosotros para resolver dudas, consultar disponibilidad o recibir apoyo en tu compra.
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
                                        Atención
                                    </p>
                                    <p class="mt-3 text-xl font-black text-neutral-900">
                                        Soporte para tu compra
                                    </p>
                                </div>

                                <div class="border-b border-neutral-200 p-6 lg:p-8">
                                    <p class="text-sm font-semibold uppercase tracking-[0.14em] text-neutral-400">
                                        Catálogo
                                    </p>
                                    <p class="mt-3 text-xl font-black text-neutral-900">
                                        Categorías, promociones y productos
                                    </p>
                                </div>

                                <div class="p-6 lg:p-8">
                                    <p class="text-sm font-semibold uppercase tracking-[0.14em] text-neutral-400">
                                        Experiencia
                                    </p>
                                    <p class="mt-3 text-xl font-black text-neutral-900">
                                        Navegación clara, visual y moderna
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
