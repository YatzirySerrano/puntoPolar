<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
import logo from '@/img/punto_polar_logo_navbar.svg';
import heroImg from '@/img/hero-tienda.jpeg';

interface Categoria {
    id: number;
    nombre: string;
    slug: string;
    imagen?: string | null;
}

interface Marca {
    id: number;
    nombre: string;
    slug?: string | null;
    logo?: string | null;
}

interface OfertaInfo {
    id: number;
    nombre: string;
    tipo: string;
    valor: number;
}

interface Producto {
    id: number;
    nombre: string;
    slug: string;
    sku: string;
    descripcion?: string | null;
    precio: number;
    precio_original?: number | null;
    precio_comparacion?: number | null;
    precio_final?: number | null;
    descuento_oferta?: number;
    tiene_oferta?: boolean;
    oferta?: OfertaInfo | null;
    stock: number;
    imagen_principal?: string | null;
    destacado?: boolean;
    categoria_id?: number | null;
    categoria?: Categoria | null;
    marca?: {
        id: number;
        nombre: string;
        slug?: string;
        logo?: string | null;
    } | null;
}

interface Banner {
    id: number;
    titulo: string;
    descripcion?: string | null;
    imagen: string | null;
    url?: string | null;
    orden?: number | null;
}

const props = defineProps<{
    categorias: Categoria[];
    destacados: Producto[];
    productos?: Producto[];
    marcas?: Marca[];
    banners?: Banner[];
}>();

const animatingProductId = ref<number | null>(null);
const toast = ref<{ show: boolean; text: string }>({
    show: false,
    text: '',
});

const contactForm = ref({
    nombre: '',
    telefono: '',
    correo: '',
    mensaje: '',
});

const featuredProducts = computed(() => {
    const source = props.destacados?.length
        ? props.destacados
        : props.productos ?? [];

    return source.slice(0, 6);
});

const processSteps = [
    {
        title: 'Filtro de sedimentos',
        text: 'Ayuda a retener partículas visibles y sólidos suspendidos.',
    },
    {
        title: 'Filtro de zeolita',
        text: 'Apoya en la reducción de impurezas presentes en el agua.',
    },
    {
        title: 'Carbón activado',
        text: 'Contribuye a mejorar olor, color y sabor del agua.',
    },
    {
        title: 'Filtro suavizador',
        text: 'Ayuda a disminuir dureza y minerales que afectan la calidad.',
    },
    {
        title: 'Ósmosis inversa',
        text: 'Proceso clave para una purificación más profunda.',
    },
    {
        title: 'Filtro pulidor',
        text: 'Etapa final para entregar agua con mejor presentación y sabor.',
    },
];

const faqs = [
    {
        question: '¿Puedo comprar en línea y recoger después?',
        answer: 'Sí. El sistema está preparado para pago anticipado y recolección en Punto Polar.',
    },
    {
        question: '¿El servicio está disponible 24/7?',
        answer: 'Sí. Punto Polar cuenta con servicio automático para agua y hielo durante todo el día.',
    },
    {
        question: '¿Manejan entrega a domicilio?',
        answer: 'Por ahora la operación principal es recolección. La entrega local quedará preparada para integrarse más adelante.',
    },
    {
        question: '¿Qué productos venden?',
        answer: 'Agua purificada en presentaciones de 20 L, 10 L y 4 L; además de hielo en presentaciones de 5 kg y 3 kg.',
    },
];

let observer: IntersectionObserver | null = null;

function initRevealOnScroll() {
    if (typeof window === 'undefined') return;

    const prefersReducedMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)',
    ).matches;

    const elements = document.querySelectorAll<HTMLElement>('[data-reveal]');

    if (prefersReducedMotion) {
        elements.forEach((element) => {
            element.classList.add('is-visible');
        });

        return;
    }

    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                } else {
                    entry.target.classList.remove('is-visible');
                }
            });
        },
        {
            threshold: 0.16,
            rootMargin: '0px 0px -8% 0px',
        },
    );

    elements.forEach((element) => observer?.observe(element));
}

function formatPrice(value: number | string | null | undefined) {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        maximumFractionDigits: 2,
    }).format(Number(value ?? 0));
}

function showToast(text: string) {
    toast.value = { show: true, text };

    window.clearTimeout((showToast as typeof showToast & { timer?: number }).timer);
    (showToast as typeof showToast & { timer?: number }).timer =
        window.setTimeout(() => {
            toast.value.show = false;
        }, 2200);
}

function triggerBubble(productId: number) {
    animatingProductId.value = productId;

    window.setTimeout(() => {
        if (animatingProductId.value === productId) {
            animatingProductId.value = null;
        }
    }, 750);
}

function addToCart(producto: Producto) {
    triggerBubble(producto.id);

    router.post(
        '/carrito/agregar',
        {
            producto_id: producto.id,
            cantidad: 1,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showToast(`"${producto.nombre}" se agregó al carrito`);
            },
            onError: () => {
                showToast('No se pudo agregar el producto al carrito');
            },
        },
    );
}

function sendContactEmail() {
    const subject = encodeURIComponent('Queja o sugerencia - Punto Polar');
    const body = encodeURIComponent(
        `Nombre: ${contactForm.value.nombre}\n` +
            `Teléfono: ${contactForm.value.telefono}\n` +
            `Correo: ${contactForm.value.correo}\n\n` +
            `Mensaje:\n${contactForm.value.mensaje}`,
    );

    window.location.href = `mailto:contactopuntopolar@gmail.com?subject=${subject}&body=${body}`;
}

onMounted(() => {
    initRevealOnScroll();
});

onBeforeUnmount(() => {
    observer?.disconnect();
});
</script>

<template>
    <PublicLayout>
        <Head title="Punto Polar · Hielo y Agua 24/7" />

        <div class="overflow-hidden bg-white text-slate-950">
            <!-- HERO -->
            <section
                class="relative min-h-screen overflow-hidden bg-[#eaf9ff] px-3 pb-8 pt-24 sm:px-5 sm:pt-28 lg:px-8 lg:pt-32"
            >
                <div class="absolute inset-0">
                    <video
                        class="h-full w-full object-cover opacity-70"
                        autoplay
                        muted
                        loop
                        playsinline
                        poster="/img/punto-polar-hero-poster.jpg"
                    >
                        <source
                            src="/videos/punto-polar-agua-hielo.mp4"
                            type="video/mp4"
                        />
                    </video>

                    <div
                        class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(48,190,239,0.24),transparent_34%),radial-gradient(circle_at_86%_18%,rgba(6,42,94,0.20),transparent_28%),linear-gradient(180deg,rgba(255,255,255,0.66)_0%,rgba(234,249,255,0.70)_46%,rgba(255,255,255,0.92)_100%)]"
                    />
                </div>

                <div
                    class="relative mx-auto flex min-h-[calc(100vh-8rem)] w-full max-w-[1500px] items-center"
                >
                    <div
                        data-reveal
                        class="reveal relative w-full overflow-hidden rounded-[42px] border border-white/45 bg-white/24 p-5 shadow-[0_34px_100px_rgba(6,42,94,0.20)] backdrop-blur-2xl sm:p-8 lg:rounded-[56px] lg:p-12"
                    >
                        <div
                            class="absolute inset-0 bg-[radial-gradient(circle_at_80%_20%,rgba(48,190,239,0.28),transparent_34%),linear-gradient(135deg,rgba(255,255,255,0.36),rgba(255,255,255,0.12))]"
                        />

                        <div
                            class="relative grid min-h-[620px] gap-10 lg:grid-cols-[0.95fr_1.05fr] lg:items-center"
                        >
                            <div class="max-w-4xl">
                                <div class="flex items-center gap-4">
                                    <img
                                        :src="logo"
                                        alt="Punto Polar"
                                        class="h-16 w-auto object-contain drop-shadow-[0_14px_30px_rgba(6,42,94,0.16)] sm:h-20"
                                    />

                                    <span
                                        class="hidden rounded-full bg-white/50 px-4 py-2 text-xs font-black uppercase tracking-[0.22em] text-[#062A5E] backdrop-blur-xl sm:inline-flex"
                                    >
                                        Agua y hielo 24/7
                                    </span>
                                </div>

                                <h1
                                    class="mt-10 max-w-5xl text-5xl font-black leading-[0.94] tracking-tight text-[#062A5E] sm:text-6xl lg:text-7xl xl:text-8xl"
                                >
                                    Frescura disponible todos los días.
                                </h1>

                                <p
                                    class="mt-7 max-w-2xl text-base leading-8 text-slate-700 sm:text-lg lg:text-xl"
                                >
                                    Agua purificada y hielo listos cuando los
                                    necesites. Compra en línea, paga de forma
                                    anticipada y recoge en Punto Polar.
                                </p>

                                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                                    <Link
                                        href="/productos"
                                        class="inline-flex h-14 items-center justify-center rounded-full bg-[#062A5E] px-7 text-sm font-black text-white shadow-[0_18px_40px_rgba(6,42,94,0.22)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#30BEEF]"
                                    >
                                        Comprar ahora
                                    </Link>

                                    <a
                                        href="#proceso-filtrado"
                                        class="inline-flex h-14 items-center justify-center rounded-full border border-[#062A5E]/10 bg-white/45 px-7 text-sm font-black text-[#062A5E] shadow-sm backdrop-blur-xl transition-all duration-300 hover:-translate-y-0.5 hover:bg-white"
                                    >
                                        Ver proceso de filtrado
                                    </a>
                                </div>
                            </div>

                            <div
                                data-reveal
                                class="reveal reveal-delay-1 relative hidden min-h-[520px] lg:block"
                            >
                                <div
                                    class="water-capsule absolute right-0 top-1/2 h-[520px] w-[320px] -translate-y-1/2 rounded-full bg-[linear-gradient(180deg,rgba(255,255,255,0.70),rgba(48,190,239,0.34),rgba(6,42,94,0.24))] shadow-[inset_0_0_80px_rgba(255,255,255,0.55),0_30px_90px_rgba(6,42,94,0.22)] backdrop-blur-xl"
                                />

                                <div
                                    class="absolute right-[120px] top-[110px] h-24 w-24 rounded-full bg-white/60 blur-2xl"
                                />

                                <div
                                    class="absolute right-[70px] top-[64px] h-10 w-10 rounded-full bg-[#30BEEF]/50 blur-xl"
                                />

                                <div
                                    class="absolute bottom-[90px] right-[210px] h-16 w-16 rounded-full bg-white/70 blur-2xl"
                                />

                                <div
                                    class="absolute right-[46px] top-1/2 h-[390px] w-[210px] -translate-y-1/2 overflow-hidden rounded-full border border-white/35 bg-white/20 shadow-[0_24px_80px_rgba(6,42,94,0.22)] backdrop-blur-2xl"
                                >
                                    <video
                                        class="h-full w-full object-cover opacity-90"
                                        autoplay
                                        muted
                                        loop
                                        playsinline
                                        poster="/img/punto-polar-hero-poster.jpg"
                                    >
                                        <source
                                            src="/videos/punto-polar-agua-hielo.mp4"
                                            type="video/mp4"
                                        />
                                    </video>

                                    <div
                                        class="absolute inset-0 bg-[linear-gradient(180deg,rgba(255,255,255,0.18),rgba(48,190,239,0.20),rgba(6,42,94,0.34))]"
                                    />
                                </div>

                                <div
                                    class="absolute bottom-14 left-0 max-w-[330px] rounded-[30px] border border-white/35 bg-white/40 p-5 shadow-[0_22px_70px_rgba(6,42,94,0.16)] backdrop-blur-2xl"
                                >
                                    <p
                                        class="text-xs font-black uppercase tracking-[0.22em] text-[#062A5E]/70"
                                    >
                                        Punto Polar
                                    </p>
                                    <p
                                        class="mt-2 text-2xl font-black leading-tight text-[#062A5E]"
                                    >
                                        Agua purificada y hielo para tu casa o negocio.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- PRODUCTOS -->
            <section id="productos-destacados" class="px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
                <div class="mx-auto w-full max-w-[1500px]">
                    <div
                        data-reveal
                        class="reveal mb-10 flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
                    >
                        <div>
                            <span
                                class="inline-flex rounded-full bg-[#30BEEF]/10 px-4 py-2 text-xs font-black uppercase tracking-[0.2em] text-[#062A5E]"
                            >
                                Productos
                            </span>

                            <h2
                                class="mt-4 max-w-4xl text-5xl font-black leading-[0.96] tracking-tight text-[#062A5E] md:text-7xl"
                            >
                                Agua y hielo para cada momento.
                            </h2>
                        </div>

                        <Link
                            href="/productos"
                            class="inline-flex h-14 items-center justify-center rounded-full bg-[#062A5E] px-6 text-sm font-black text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#30BEEF]"
                        >
                            Ver catálogo completo
                        </Link>
                    </div>

                    <div
                        v-if="featuredProducts.length"
                        class="-mx-4 flex gap-5 overflow-x-auto px-4 pb-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
                    >
                        <article
                            v-for="(producto, index) in featuredProducts"
                            :key="producto.id"
                            data-reveal
                            class="reveal group flex min-h-[520px] w-[310px] shrink-0 snap-start flex-col overflow-hidden rounded-[34px] bg-white shadow-[0_18px_54px_rgba(6,42,94,0.08)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_24px_70px_rgba(6,42,94,0.12)] sm:w-[360px]"
                            :class="[
                                index === 0 ? 'lg:w-[460px]' : '',
                                index === 2 ? 'lg:w-[430px]' : '',
                                index === 4 ? 'lg:w-[390px]' : '',
                                `reveal-delay-${Math.min(index, 3)}`,
                            ]"
                        >
                            <div
                                class="relative overflow-hidden bg-[#eaf9ff]"
                                :class="index % 2 === 0 ? 'h-[300px] rounded-b-[42px]' : 'h-[250px] rounded-b-[120px]'"
                            >
                                <img
                                    :src="producto.imagen_principal || heroImg"
                                    :alt="producto.nombre"
                                    class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-[1.06]"
                                />

                                <div
                                    class="absolute inset-0 bg-[linear-gradient(180deg,rgba(6,42,94,0.02)_0%,rgba(6,42,94,0.36)_100%)]"
                                />

                                <div class="absolute left-4 top-4 flex flex-wrap gap-2">
                                    <span
                                        class="rounded-full bg-white/90 px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-[#062A5E]"
                                    >
                                        {{ producto.categoria?.nombre || 'Producto' }}
                                    </span>

                                    <span
                                        v-if="producto.tiene_oferta"
                                        class="rounded-full bg-[#30BEEF] px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-white"
                                    >
                                        Promo
                                    </span>
                                </div>
                            </div>

                            <div class="flex flex-1 flex-col p-5">
                                <p
                                    class="text-xs font-black uppercase tracking-[0.18em] text-slate-400"
                                >
                                    {{ producto.sku }}
                                </p>

                                <h3
                                    class="mt-3 line-clamp-2 text-2xl font-black leading-tight text-slate-950"
                                >
                                    {{ producto.nombre }}
                                </h3>

                                <p
                                    v-if="producto.descripcion"
                                    class="mt-3 line-clamp-2 text-sm leading-6 text-slate-600"
                                >
                                    {{ producto.descripcion }}
                                </p>

                                <div class="mt-auto pt-5">
                                    <div class="flex items-end justify-between gap-4">
                                        <div>
                                            <p
                                                v-if="producto.precio_original && producto.precio_original > producto.precio"
                                                class="text-sm text-slate-400 line-through"
                                            >
                                                {{ formatPrice(producto.precio_original) }}
                                            </p>

                                            <p class="text-3xl font-black text-[#062A5E]">
                                                {{ formatPrice(producto.precio) }}
                                            </p>
                                        </div>

                                        <p
                                            class="rounded-full bg-[#eaf9ff] px-3 py-1 text-xs font-black text-[#062A5E]"
                                        >
                                            {{ producto.stock > 0 ? 'Disponible' : 'Sin stock' }}
                                        </p>
                                    </div>

                                    <div class="mt-5 grid grid-cols-2 gap-2">
                                        <Link
                                            :href="`/productos/${producto.slug}`"
                                            class="inline-flex h-12 items-center justify-center rounded-full border border-[#062A5E]/12 bg-white px-4 text-sm font-black text-[#062A5E] transition-all duration-300 hover:bg-[#eaf9ff]"
                                        >
                                            Ver detalle
                                        </Link>

                                        <button
                                            type="button"
                                            class="relative inline-flex h-12 items-center justify-center rounded-full bg-[linear-gradient(135deg,#30BEEF_0%,#062A5E_100%)] px-4 text-sm font-black text-white transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_14px_30px_rgba(48,190,239,0.26)] disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-white"
                                            :disabled="producto.stock < 1"
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

                                            <span class="relative z-10">
                                                {{ producto.stock < 1 ? 'Sin stock' : 'Agregar' }}
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div
                        v-else
                        data-reveal
                        class="reveal rounded-[34px] border border-slate-200 bg-[#eaf9ff] p-10 text-center"
                    >
                        <h3 class="text-2xl font-black text-[#062A5E]">
                            Agrega productos destacados para mostrarlos aquí.
                        </h3>

                        <p class="mt-2 text-slate-600">
                            Cuando actives productos destacados, aparecerán en
                            esta sección.
                        </p>
                    </div>
                </div>
            </section>

            <!-- CÓMO COMPRAR -->
            <section
                id="como-comprar"
                class="bg-[#062A5E] px-4 py-16 text-white sm:px-6 lg:px-8 lg:py-20"
            >
                <div class="mx-auto w-full max-w-[1500px]">
                    <div
                        data-reveal
                        class="reveal grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-end"
                    >
                        <div>
                            <span
                                class="inline-flex rounded-full bg-white/10 px-4 py-2 text-xs font-black uppercase tracking-[0.2em] text-white/70"
                            >
                                Cómo comprar
                            </span>

                            <h2 class="mt-4 text-4xl font-black tracking-tight md:text-6xl">
                                Elige, paga y recoge.
                            </h2>

                            <p class="mt-4 max-w-xl text-base leading-7 text-white/70">
                                El sistema está pensado para que puedas hacer tu
                                pedido en línea y pasar por tus productos a
                                Punto Polar.
                            </p>
                        </div>

                        <div class="grid gap-4 md:grid-cols-3">
                            <div
                                data-reveal
                                class="reveal reveal-delay-1 rounded-[30px] bg-white/10 p-6 backdrop-blur-xl"
                            >
                                <p class="text-4xl font-black text-[#30BEEF]">01</p>
                                <h3 class="mt-5 text-xl font-black">
                                    Elige tus productos
                                </h3>
                                <p class="mt-3 text-sm leading-6 text-white/70">
                                    Selecciona agua, hielo o promociones desde el
                                    catálogo.
                                </p>
                            </div>

                            <div
                                data-reveal
                                class="reveal reveal-delay-2 rounded-[30px] bg-white/10 p-6 backdrop-blur-xl"
                            >
                                <p class="text-4xl font-black text-[#30BEEF]">02</p>
                                <h3 class="mt-5 text-xl font-black">
                                    Realiza tu pago
                                </h3>
                                <p class="mt-3 text-sm leading-6 text-white/70">
                                    Confirma tu compra con pago anticipado en
                                    línea.
                                </p>
                            </div>

                            <div
                                data-reveal
                                class="reveal reveal-delay-3 rounded-[30px] bg-white/10 p-6 backdrop-blur-xl"
                            >
                                <p class="text-4xl font-black text-[#30BEEF]">03</p>
                                <h3 class="mt-5 text-xl font-black">
                                    Recoge en Punto Polar
                                </h3>
                                <p class="mt-3 text-sm leading-6 text-white/70">
                                    Presenta tu código de recolección y recibe
                                    tus productos.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- PROCESO DE FILTRADO -->
            <section
                id="proceso-filtrado"
                class="px-4 py-16 sm:px-6 lg:px-8 lg:py-20"
            >
                <div class="mx-auto w-full max-w-[1500px]">
                    <div
                        class="grid gap-10 lg:grid-cols-[0.85fr_1.15fr] lg:items-start"
                    >
                        <div data-reveal class="reveal lg:sticky lg:top-32">
                            <span
                                class="inline-flex rounded-full bg-[#30BEEF]/10 px-4 py-2 text-xs font-black uppercase tracking-[0.2em] text-[#062A5E]"
                            >
                                Proceso de filtrado
                            </span>

                            <h2
                                class="mt-4 text-4xl font-black tracking-tight text-slate-950 md:text-6xl"
                            >
                                Confianza en cada llenado.
                            </h2>

                            <p class="mt-4 max-w-xl text-base leading-7 text-slate-600">
                                Nuestro sistema incluye etapas de purificación
                                como filtración de sedimentos, zeolita, carbón
                                activado, suavizador, ósmosis inversa y filtro
                                pulidor.
                            </p>

                            <div
                                class="mt-6 rounded-[30px] bg-[#eaf9ff] p-5 text-sm leading-7 text-slate-700"
                            >
                                <strong class="text-[#062A5E]">
                                    Próximamente:
                                </strong>
                                una página independiente para explicar con más
                                detalle cada etapa del proceso.
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <article
                                v-for="(step, index) in processSteps"
                                :key="step.title"
                                data-reveal
                                class="reveal rounded-[32px] border border-slate-200 bg-white p-6 shadow-[0_18px_54px_rgba(6,42,94,0.07)]"
                                :class="`reveal-delay-${Math.min(index, 3)}`"
                            >
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#30BEEF]/10 text-lg font-black text-[#062A5E]"
                                >
                                    {{ index + 1 }}
                                </div>

                                <h3 class="mt-5 text-xl font-black text-slate-950">
                                    {{ step.title }}
                                </h3>

                                <p class="mt-3 text-sm leading-7 text-slate-600">
                                    {{ step.text }}
                                </p>
                            </article>
                        </div>
                    </div>
                </div>
            </section>

            <!-- FAQ -->
            <section
                id="preguntas-frecuentes"
                class="bg-[#f6fbff] px-4 py-16 sm:px-6 lg:px-8 lg:py-20"
            >
                <div class="mx-auto w-full max-w-[1200px]">
                    <div data-reveal class="reveal text-center">
                        <span
                            class="inline-flex rounded-full bg-white px-4 py-2 text-xs font-black uppercase tracking-[0.2em] text-[#062A5E] shadow-sm"
                        >
                            Preguntas frecuentes
                        </span>

                        <h2
                            class="mt-4 text-4xl font-black tracking-tight text-slate-950 md:text-6xl"
                        >
                            Antes de comprar.
                        </h2>
                    </div>

                    <div class="mt-10 grid gap-4">
                        <article
                            v-for="(faq, index) in faqs"
                            :key="faq.question"
                            data-reveal
                            class="reveal rounded-[30px] border border-slate-200 bg-white p-6 shadow-[0_18px_54px_rgba(6,42,94,0.06)]"
                            :class="`reveal-delay-${Math.min(index, 3)}`"
                        >
                            <h3 class="text-xl font-black text-[#062A5E]">
                                {{ faq.question }}
                            </h3>

                            <p class="mt-3 text-sm leading-7 text-slate-600">
                                {{ faq.answer }}
                            </p>
                        </article>
                    </div>
                </div>
            </section>

            <!-- CONTACTO -->
            <section
                id="contacto-home"
                class="px-4 py-16 sm:px-6 lg:px-8 lg:py-20"
            >
                <div
                    class="mx-auto grid w-full max-w-[1500px] gap-10 rounded-[42px] bg-[linear-gradient(135deg,#062A5E_0%,#0B5FA5_52%,#30BEEF_100%)] p-6 text-white shadow-[0_30px_90px_rgba(6,42,94,0.20)] md:p-10 lg:grid-cols-[0.9fr_1.1fr]"
                >
                    <div data-reveal class="reveal">
                        <span
                            class="inline-flex rounded-full bg-white/10 px-4 py-2 text-xs font-black uppercase tracking-[0.2em] text-white/70"
                        >
                            Contacto
                        </span>

                        <h2 class="mt-4 text-4xl font-black tracking-tight md:text-6xl">
                            Quejas, dudas o sugerencias.
                        </h2>

                        <p class="mt-4 max-w-xl text-base leading-8 text-white/75">
                            Escríbenos para reportar alguna situación, compartir
                            una sugerencia o solicitar apoyo relacionado con tu
                            compra.
                        </p>

                        <div class="mt-8 rounded-[30px] bg-white/10 p-5 backdrop-blur-xl">
                            <p class="text-sm font-black uppercase tracking-[0.18em] text-white/60">
                                Correo
                            </p>
                            <p class="mt-2 text-xl font-black">
                                contactopuntopolar@gmail.com
                            </p>
                        </div>
                    </div>

                    <form
                        data-reveal
                        class="reveal reveal-delay-1 rounded-[34px] bg-white p-5 text-slate-950 shadow-[0_24px_70px_rgba(6,42,94,0.18)] md:p-6"
                        @submit.prevent="sendContactEmail"
                    >
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label>
                                <span class="text-sm font-black text-slate-700">
                                    Nombre
                                </span>
                                <input
                                    v-model="contactForm.nombre"
                                    type="text"
                                    required
                                    class="mt-2 h-12 w-full rounded-2xl border border-slate-200 px-4 text-sm outline-none transition focus:border-[#30BEEF] focus:ring-4 focus:ring-[#30BEEF]/15"
                                />
                            </label>

                            <label>
                                <span class="text-sm font-black text-slate-700">
                                    Teléfono
                                </span>
                                <input
                                    v-model="contactForm.telefono"
                                    type="text"
                                    class="mt-2 h-12 w-full rounded-2xl border border-slate-200 px-4 text-sm outline-none transition focus:border-[#30BEEF] focus:ring-4 focus:ring-[#30BEEF]/15"
                                />
                            </label>

                            <label class="sm:col-span-2">
                                <span class="text-sm font-black text-slate-700">
                                    Correo
                                </span>
                                <input
                                    v-model="contactForm.correo"
                                    type="email"
                                    class="mt-2 h-12 w-full rounded-2xl border border-slate-200 px-4 text-sm outline-none transition focus:border-[#30BEEF] focus:ring-4 focus:ring-[#30BEEF]/15"
                                />
                            </label>

                            <label class="sm:col-span-2">
                                <span class="text-sm font-black text-slate-700">
                                    Mensaje
                                </span>
                                <textarea
                                    v-model="contactForm.mensaje"
                                    rows="5"
                                    required
                                    class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-[#30BEEF] focus:ring-4 focus:ring-[#30BEEF]/15"
                                />
                            </label>
                        </div>

                        <button
                            type="submit"
                            class="mt-5 inline-flex h-14 w-full items-center justify-center rounded-full bg-[#062A5E] px-6 text-sm font-black text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#30BEEF]"
                        >
                            Enviar mensaje
                        </button>

                        <p class="mt-3 text-xs leading-5 text-slate-500">
                            Por ahora este formulario abre tu cliente de correo
                            para enviar el mensaje. Después podemos conectarlo
                            directamente a Laravel.
                        </p>
                    </form>
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
                class="fixed bottom-4 right-4 z-50 flex items-center gap-3 rounded-2xl border border-white/60 bg-white px-4 py-3 shadow-[0_16px_40px_rgba(6,42,94,0.15)]"
            >
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#30BEEF]/14 text-[#062A5E]"
                >
                    ✓
                </div>
                <div>
                    <p class="text-sm font-black text-slate-900">
                        Carrito actualizado
                    </p>
                    <p class="text-sm text-slate-500">{{ toast.text }}</p>
                </div>
            </div>
        </Transition>
    </PublicLayout>
</template>

<style scoped>
.reveal {
    opacity: 0;
    transform: translateY(28px);
    transition:
        opacity 700ms ease,
        transform 700ms ease;
}

.reveal.is-visible {
    opacity: 1;
    transform: translateY(0);
}

.reveal-delay-1 {
    transition-delay: 90ms;
}

.reveal-delay-2 {
    transition-delay: 160ms;
}

.reveal-delay-3 {
    transition-delay: 230ms;
}

.water-capsule {
    animation: water-float 6s ease-in-out infinite;
}

@keyframes water-float {
    0%,
    100% {
        transform: translateY(-50%) translateX(0) rotate(0deg);
    }

    50% {
        transform: translateY(-52%) translateX(-10px) rotate(1deg);
    }
}

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
    background: radial-gradient(
        circle,
        rgba(48, 190, 239, 0.95) 0%,
        rgba(6, 42, 94, 0.95) 100%
    );
}

.bubble-1 {
    --tx: -24px;
    --ty: -34px;
    animation-delay: 0s;
}

.bubble-2 {
    --tx: 28px;
    --ty: -30px;
    animation-delay: 0.04s;
}

.bubble-3 {
    --tx: -30px;
    --ty: 8px;
    animation-delay: 0.08s;
}

.bubble-4 {
    --tx: 34px;
    --ty: 10px;
    animation-delay: 0.12s;
}

.bubble-5 {
    --tx: 0px;
    --ty: -42px;
    animation-delay: 0.16s;
}

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

@media (prefers-reduced-motion: reduce) {
    .reveal {
        opacity: 1;
        transform: none;
        transition: none;
    }

    .water-capsule {
        animation: none;
    }

    .bubble {
        animation: none;
        opacity: 0;
    }
}
</style>