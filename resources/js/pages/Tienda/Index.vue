<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
import logo from '@/img/punto_polar_logo_navbar.svg';
import heroImg from '@/img/png/fondo-hero.png';
import heroImgMobile from '@/img/png/fondo-hero-mobile.png';
import faqBg from '@/img/png/faq-bg2.png';
import faqBgMobile from '@/img/png/faq-bg-mobile.png';
import comoComprarBg from '@/img/png/como-comprar-bg.png';

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
const activeFaq = ref<number | null>(0);
const quantities = ref<Record<number, number>>({});

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

    return source.slice(0, 2);
});

const processSteps = [
    {
        number: '01',
        icon: '◌',
        title: 'Sedimentos',
        subtitle: 'Filtro de sedimentos',
        text: 'Retiene partículas visibles y sólidos suspendidos antes de continuar con las siguientes etapas.',
    },
    {
        number: '02',
        icon: '◆',
        title: 'Zeolita',
        subtitle: 'Filtro de zeolita',
        text: 'Ayuda a reducir impurezas y mejora el tratamiento inicial del agua.',
    },
    {
        number: '03',
        icon: '●',
        title: 'Carbón activado',
        subtitle: 'Filtro de carbón activado',
        text: 'Contribuye a mejorar olor, color y sabor para una experiencia más agradable.',
    },
    {
        number: '04',
        icon: '≈',
        title: 'Suavizador',
        subtitle: 'Filtro suavizador',
        text: 'Disminuye dureza y minerales que pueden afectar la calidad del agua.',
    },
    {
        number: '05',
        icon: '↻',
        title: 'Ósmosis inversa',
        subtitle: 'Sistema de ósmosis inversa',
        text: 'Proceso clave para una purificación más profunda y confiable.',
    },
    {
        number: '06',
        icon: '✦',
        title: 'Pulidor',
        subtitle: 'Filtro pulidor',
        text: 'Etapa final para entregar agua con mejor presentación, claridad y sabor.',
    },
];

const buySteps = [
    {
        number: '01',
        title: 'Elige tus productos',
        text: 'Selecciona agua, hielo o promociones desde el catálogo.',
        image: comoComprarBg,
    },
    {
        number: '02',
        title: 'Realiza tu pago',
        text: 'Confirma tu compra con pago anticipado en línea.',
        image: comoComprarBg,
    },
    {
        number: '03',
        title: 'Pedido confirmado',
        text: 'Recibirás una notificación por correo cuando tu pedido quede registrado.',
        image: comoComprarBg,
    },
    {
        number: '04',
        title: 'Preparación',
        text: 'Te avisaremos cuando el equipo comience a preparar tu compra.',
        image: comoComprarBg,
    },
    {
        number: '05',
        title: 'Listo para recoger',
        text: 'Recibirás un correo cuando tu pedido esté listo.',
        image: comoComprarBg,
    },
    {
        number: '06',
        title: 'Recoge en Punto Polar',
        text: 'Presenta tu código de recolección y recibe tus productos.',
        image: comoComprarBg,
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
    {
        question: '¿Cómo sé que mi pedido ya está confirmado?',
        answer: 'Después del pago, el sistema registra el pedido y podrás consultar el estado desde tu cuenta.',
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

    window.clearTimeout(
        (showToast as typeof showToast & { timer?: number }).timer,
    );

    (showToast as typeof showToast & { timer?: number }).timer =
        window.setTimeout(() => {
            toast.value.show = false;
        }, 3500);
}

function getQuantity(producto: Producto) {
    return quantities.value[producto.id] ?? 1;
}

function increaseQuantity(producto: Producto) {
    const current = getQuantity(producto);
    const max = producto.stock > 0 ? producto.stock : current + 1;

    quantities.value[producto.id] = Math.min(current + 1, max);
}

function decreaseQuantity(producto: Producto) {
    const current = getQuantity(producto);

    quantities.value[producto.id] = Math.max(current - 1, 1);
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
    const cantidad = getQuantity(producto);

    triggerBubble(producto.id);

    router.post(
        '/carrito/agregar',
        {
            producto_id: producto.id,
            cantidad,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showToast(
                    `${cantidad} ${cantidad === 1 ? 'pieza' : 'piezas'} de "${producto.nombre}" se agregaron al carrito`,
                );
            },
            onError: () => {
                showToast('No se pudo agregar el producto al carrito');
            },
        },
    );
}

function toggleFaq(index: number) {
    activeFaq.value = activeFaq.value === index ? null : index;
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
                class="relative min-h-[92vh] overflow-hidden bg-[#062A5E] px-4 pb-10 pt-24 sm:px-6 sm:pt-28 lg:min-h-screen lg:px-8 lg:pt-30"
            >
                <div class="absolute inset-0">
                    <picture>
                        <source
                            media="(max-width: 767px)"
                            :srcset="heroImgMobile"
                        />
                        <img
                            :src="heroImg"
                            alt="Agua purificada y hielo Punto Polar"
                            class="h-full w-full object-cover object-center md:object-center"
                        />
                    </picture>

                    <div
                        class="absolute inset-0 bg-[linear-gradient(90deg,rgba(6,42,94,0.90)_0%,rgba(6,42,94,0.72)_38%,rgba(6,42,94,0.30)_72%,rgba(255,255,255,0.16)_100%)]"
                    />

                    <div
                        class="absolute inset-0 bg-[radial-gradient(circle_at_18%_18%,rgba(48,190,239,0.28),transparent_32%),radial-gradient(circle_at_82%_18%,rgba(255,255,255,0.18),transparent_28%)]"
                    />
                </div>

                <div
                    class="relative mx-auto flex min-h-[calc(92vh-8rem)] w-full max-w-[1500px] items-start lg:min-h-[calc(100vh-8rem)]"
                >
                    <div
                        data-reveal
                        class="reveal max-w-5xl pb-8 pt-8 text-white sm:pt-10 lg:pt-14"
                    >
                        <img
                            :src="logo"
                            alt="Punto Polar"
                            class="mb-7 h-24 w-auto object-contain drop-shadow-[0_22px_50px_rgba(0,0,0,0.26)] sm:h-28 lg:h-32"
                        />

                        <span
                            class="inline-flex items-center gap-3 rounded-full border border-white/28 bg-white/14 px-5 py-3 text-xs font-black uppercase tracking-[0.22em] text-white/85 shadow-[0_18px_40px_rgba(0,0,0,0.14)] backdrop-blur-xl sm:text-sm"
                        >
                            Agua purificada y hielo
                            <strong
                                class="rounded-full bg-[#30BEEF] px-3 py-1 text-white shadow-[0_12px_24px_rgba(48,190,239,0.30)]"
                            >
                                24/7
                            </strong>
                        </span>

                        <h1
                            class="mt-7 max-w-5xl text-5xl font-black leading-[0.92] tracking-tight text-white sm:text-6xl lg:text-7xl xl:text-8xl"
                        >
                            Frescura disponible todos los días.
                        </h1>

                        <p
                            class="mt-7 max-w-2xl text-base leading-8 text-white/78 sm:text-lg lg:text-xl"
                        >
                            Compra agua purificada y hielo en línea, paga de
                            forma anticipada y recoge en Punto Polar cuando lo
                            necesites.
                        </p>

                        <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                            <Link
                                href="/productos"
                                class="inline-flex h-14 items-center justify-center rounded-full bg-white px-7 text-sm font-black text-[#062A5E] shadow-[0_18px_40px_rgba(0,0,0,0.20)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#30BEEF] hover:text-white"
                            >
                                Comprar ahora
                            </Link>

                            <a
                                href="#proceso-filtrado"
                                class="inline-flex h-14 items-center justify-center rounded-full border border-white/25 bg-white/12 px-7 text-sm font-black text-white shadow-sm backdrop-blur-xl transition-all duration-300 hover:-translate-y-0.5 hover:bg-white/22"
                            >
                                Ver proceso de filtrado
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- PROCESO DE FILTRADO -->
            <section
                id="proceso-filtrado"
                class="relative overflow-hidden bg-white px-4 py-16 sm:px-6 lg:px-8 lg:py-24"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_10%_10%,rgba(48,190,239,0.13),transparent_30%),radial-gradient(circle_at_90%_20%,rgba(6,42,94,0.08),transparent_28%)]"
                />

                <div class="relative mx-auto w-full max-w-[1500px]">
                    <div data-reveal class="reveal text-center">
                        <p
                            class="text-xs font-black uppercase tracking-[0.34em] text-[#062A5E]/45"
                        >
                            Sistema de purificación
                        </p>

                        <h2
                            class="mx-auto mt-5 max-w-5xl text-5xl font-black leading-[0.94] tracking-tight text-[#062A5E] md:text-7xl"
                        >
                            Seis etapas para darte más confianza.
                        </h2>

                        <p
                            class="mx-auto mt-6 max-w-3xl text-base leading-8 text-slate-600 md:text-lg"
                        >
                            Nuestro tren de filtrado combina sedimentos,
                            zeolita, carbón activado, suavizador, ósmosis
                            inversa y filtro pulidor para cuidar la calidad del
                            agua.
                        </p>
                    </div>

                    <div class="mt-14 grid gap-6 lg:grid-cols-3">
                        <article
                            v-for="(step, index) in processSteps"
                            :key="step.title"
                            data-reveal
                            class="reveal group relative min-h-[300px] overflow-hidden rounded-[34px] border border-slate-200 bg-white/82 p-7 shadow-[0_22px_70px_rgba(6,42,94,0.08)] backdrop-blur-xl transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_28px_90px_rgba(6,42,94,0.14)]"
                            :class="`reveal-delay-${Math.min(index, 3)}`"
                        >
                            <span
                                class="pointer-events-none absolute -top-8 left-5 text-[9rem] font-black leading-none text-slate-100 transition duration-300 group-hover:text-[#30BEEF]/10 md:text-[10rem]"
                            >
                                {{ step.number }}
                            </span>

                            <div class="relative z-10">
                                <div
                                    class="flex h-16 w-16 items-center justify-center rounded-[24px] bg-[#eaf9ff] text-3xl font-black text-[#062A5E] shadow-inner"
                                >
                                    {{ step.icon }}
                                </div>

                                <p
                                    class="mt-8 text-xs font-black uppercase tracking-[0.22em] text-[#30BEEF]"
                                >
                                    {{ step.subtitle }}
                                </p>

                                <h3
                                    class="mt-3 text-3xl font-black tracking-tight text-[#062A5E]"
                                >
                                    {{ step.title }}
                                </h3>

                                <p class="mt-4 text-sm leading-7 text-slate-600">
                                    {{ step.text }}
                                </p>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <!-- CÓMO COMPRAR -->
            <section
                id="como-comprar"
                class="relative overflow-hidden bg-[#f6fbff] px-4 py-16 sm:px-6 lg:px-8 lg:py-24"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_10%_0%,rgba(48,190,239,0.16),transparent_30%),radial-gradient(circle_at_92%_15%,rgba(6,42,94,0.10),transparent_28%)]"
                />

                <div class="relative mx-auto w-full max-w-[1500px]">
                    <div
                        data-reveal
                        class="reveal mb-12 flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
                    >
                        <div class="max-w-4xl">
                            <span
                                class="inline-flex rounded-full bg-white px-4 py-2 text-xs font-black uppercase tracking-[0.2em] text-[#062A5E] shadow-sm"
                            >
                                Cómo comprar
                            </span>

                            <h2
                                class="mt-4 text-4xl font-black tracking-tight text-[#062A5E] md:text-6xl"
                            >
                                Compra en línea y sigue cada etapa.
                            </h2>

                            <p
                                class="mt-4 max-w-2xl text-base leading-7 text-slate-600"
                            >
                                Hacer tu pedido es simple: seleccionas,
                                confirmas tu pago y te mantenemos informado
                                hasta que esté listo para recoger.
                            </p>
                        </div>
                    </div>

                    <div
                        class="relative grid gap-3 md:block md:h-[565px] lg:h-[710px]"
                    >
                        <article
                            v-for="(step, index) in buySteps"
                            :key="step.title"
                            data-reveal
                            class="reveal group relative min-h-[230px] overflow-hidden rounded-[34px] border border-white/70 bg-white shadow-[0_18px_54px_rgba(6,42,94,0.08)] transition-all duration-500 hover:-translate-y-1 hover:shadow-[0_28px_80px_rgba(6,42,94,0.15)]"
                            :class="[
                                `reveal-delay-${Math.min(index, 3)}`,

                            index === 0
                                ? 'md:absolute md:left-0 md:top-0 md:h-[305px] md:w-[37%] lg:h-[430px]'
                                : '',

                            index === 1
                                ? 'md:absolute md:left-1/2 md:top-0 md:h-[270px] md:w-[25%] md:-translate-x-1/2 lg:h-[310px]'
                                : '',

                            index === 2
                                ? 'md:absolute md:right-0 md:top-0 md:h-[305px] md:w-[37%] lg:h-[430px]'
                                : '',

                            index === 3
                                ? 'md:absolute md:left-0 md:top-[325px] md:h-[220px] md:w-[37%] lg:top-[455px] lg:h-[235px]'
                                : '',

                            index === 4
                                ? 'md:absolute md:left-1/2 md:top-[295px] md:h-[270px] md:w-[25%] md:-translate-x-1/2 lg:top-[335px] lg:h-[310px]'
                                : '',

                            index === 5
                                ? 'md:absolute md:right-0 md:top-[325px] md:h-[220px] md:w-[37%] lg:top-[455px] lg:h-[235px]'
                                : '',
                            ]"
                        >
                            <img
                                :src="step.image"
                                :alt="step.title"
                                class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                            />

                            <div
                                class="absolute inset-0 transition duration-500"
                                :class="[
                                    index === 1
                                        ? 'bg-[linear-gradient(135deg,rgba(3,7,18,0.86),rgba(3,7,18,0.62))]'
                                        : '',

                                    index === 4
                                        ? 'bg-[linear-gradient(180deg,rgba(255,255,255,0.88),rgba(255,255,255,0.74))]'
                                        : '',

                                    index === 3 || index === 5
                                        ? 'bg-[linear-gradient(135deg,rgba(6,42,94,0.84),rgba(11,95,165,0.60))]'
                                        : '',

                                    index === 0 || index === 2
                                        ? 'bg-[linear-gradient(135deg,rgba(48,190,239,0.68),rgba(6,42,94,0.56))]'
                                        : '',
                                ]"
                            />

                            <span
                                class="absolute right-5 top-4 font-black leading-none transition duration-300"
                                :class="[
                                    index === 4
                                        ? 'text-[#062A5E]/8'
                                        : 'text-white/14',
                                    index === 1 || index === 4
                                        ? 'text-5xl lg:text-6xl'
                                        : 'text-7xl lg:text-8xl',
                                ]"
                            >
                                {{ step.number }}
                            </span>

                            <div
                                class="relative z-10 flex h-full flex-col justify-between p-6"
                                :class="index === 1 || index === 4 ? 'lg:p-5' : 'lg:p-7'"
                            >
                                <div>
                                    <p
                                        class="text-xs font-black uppercase tracking-[0.22em]"
                                        :class="
                                            index === 4
                                                ? 'text-[#30BEEF]'
                                                : 'text-white/75'
                                        "
                                    >
                                        Paso {{ step.number }}
                                    </p>

                                    <h3
                                        class="mt-3 font-black leading-tight tracking-tight"
                                        :class="[
                                            index === 4
                                                ? 'text-[#062A5E]'
                                                : 'text-white',
                                            index === 1 || index === 4
                                                ? 'text-xl lg:text-2xl'
                                                : 'text-2xl lg:text-3xl',
                                        ]"
                                    >
                                        {{ step.title }}
                                    </h3>
                                </div>

                                <p
                                    class="mt-6 max-w-[18rem] text-sm leading-6"
                                    :class="
                                        index === 4
                                            ? 'text-slate-600'
                                            : 'text-white/82'
                                    "
                                >
                                    {{ step.text }}
                                </p>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <!-- PRODUCTOS -->
            <section
                id="productos-destacados"
                class="px-4 py-16 sm:px-6 lg:px-8 lg:py-24"
            >
                <div class="mx-auto w-full max-w-[1500px]">
                    <div data-reveal class="reveal mb-10 max-w-5xl">
                        <span
                            class="inline-flex rounded-full bg-[#30BEEF]/10 px-4 py-2 text-xs font-black uppercase tracking-[0.2em] text-[#062A5E]"
                        >
                            Más pedidos
                        </span>

                        <h2
                            class="mt-4 max-w-4xl text-5xl font-black leading-[0.96] tracking-tight text-[#062A5E] md:text-7xl"
                        >
                            Nuestros productos más pedidos.
                        </h2>

                        <p
                            class="mt-5 max-w-3xl text-base leading-8 text-slate-600 md:text-lg"
                        >
                            Agua y hielo listos para resolver lo esencial: tu
                            garrafón para el día a día y hielo para conservar,
                            compartir o vender.
                        </p>
                    </div>

                    <div
                        v-if="featuredProducts.length"
                        class="grid gap-6 md:grid-cols-2 xl:grid-cols-3"
                    >
                        <article
                            v-for="(producto, index) in featuredProducts"
                            :key="producto.id"
                            data-reveal
                            class="reveal group flex min-h-[500px] flex-col overflow-hidden rounded-[34px] bg-white shadow-[0_18px_54px_rgba(6,42,94,0.08)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_24px_70px_rgba(6,42,94,0.12)]"
                            :class="`reveal-delay-${Math.min(index, 3)}`"
                        >
                            <div
                                class="relative h-[260px] overflow-hidden rounded-b-[42px] bg-[#eaf9ff] sm:h-[290px] xl:h-[300px]"
                            >
                                <img
                                    :src="producto.imagen_principal || heroImg"
                                    :alt="producto.nombre"
                                    class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-[1.06]"
                                />

                                <div
                                    class="absolute inset-0 bg-[linear-gradient(180deg,rgba(6,42,94,0.02)_0%,rgba(6,42,94,0.36)_100%)]"
                                />

                                <div
                                    class="absolute left-4 top-4 flex flex-wrap gap-2"
                                >
                                    <span
                                        class="rounded-full bg-white/90 px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-[#062A5E]"
                                    >
                                        {{
                                            producto.categoria?.nombre ||
                                            'Producto'
                                        }}
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
                                    <div
                                        class="flex items-end justify-between gap-4"
                                    >
                                        <div>
                                            <p
                                                v-if="
                                                    producto.precio_original &&
                                                    producto.precio_original >
                                                        producto.precio
                                                "
                                                class="text-sm text-slate-400 line-through"
                                            >
                                                {{
                                                    formatPrice(
                                                        producto.precio_original,
                                                    )
                                                }}
                                            </p>

                                            <p
                                                class="text-3xl font-black text-[#062A5E]"
                                            >
                                                {{ formatPrice(producto.precio) }}
                                            </p>
                                        </div>

                                        <p
                                            class="rounded-full bg-[#eaf9ff] px-3 py-1 text-xs font-black text-[#062A5E]"
                                        >
                                            {{
                                                producto.stock > 0
                                                    ? 'Disponible'
                                                    : 'Sin stock'
                                            }}
                                        </p>
                                    </div>

                                    <div
                                        class="mt-5 grid grid-cols-1 gap-2 sm:grid-cols-[auto_1fr]"
                                    >
                                        <div
                                            class="inline-flex h-12 w-full items-center justify-between rounded-full border border-[#062A5E]/12 bg-[#f6fbff] p-1 sm:w-auto"
                                        >
                                            <button
                                                type="button"
                                                class="flex h-10 w-10 items-center justify-center rounded-full text-lg font-black text-[#062A5E] transition hover:bg-white"
                                                aria-label="Disminuir cantidad"
                                                @click="decreaseQuantity(producto)"
                                            >
                                                −
                                            </button>

                                            <span
                                                class="flex h-10 min-w-10 items-center justify-center px-2 text-sm font-black text-[#062A5E]"
                                            >
                                                {{ getQuantity(producto) }}
                                            </span>

                                            <button
                                                type="button"
                                                class="flex h-10 w-10 items-center justify-center rounded-full text-lg font-black text-[#062A5E] transition hover:bg-white"
                                                aria-label="Aumentar cantidad"
                                                @click="increaseQuantity(producto)"
                                            >
                                                +
                                            </button>
                                        </div>

                                        <button
                                            type="button"
                                            class="relative inline-flex h-12 w-full items-center justify-center gap-2 rounded-full bg-[linear-gradient(135deg,#30BEEF_0%,#062A5E_100%)] px-4 text-sm font-black text-white transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_14px_30px_rgba(48,190,239,0.26)] disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-white"
                                            :disabled="producto.stock < 1"
                                            @click="addToCart(producto)"
                                        >
                                            <span
                                                v-if="
                                                    animatingProductId ===
                                                    producto.id
                                                "
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
                                                stroke-width="2"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M3 3h2l2.2 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.5L21 7H6"
                                                />
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M10 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm8 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"
                                                />
                                            </svg>

                                            <span class="relative z-10">
                                                {{
                                                    producto.stock < 1
                                                        ? 'Sin stock'
                                                        : 'Agregar'
                                                }}
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <article
                            data-reveal
                            class="reveal reveal-delay-2 flex min-h-[500px] flex-col justify-between overflow-hidden rounded-[34px] bg-[#062A5E] p-7 text-white shadow-[0_24px_70px_rgba(6,42,94,0.20)]"
                        >
                            <div>
                                <p
                                    class="text-xs font-black uppercase tracking-[0.22em] text-white/50"
                                >
                                    Catálogo completo
                                </p>

                                <h3
                                    class="mt-5 text-4xl font-black leading-tight tracking-tight"
                                >
                                    Explora todas las presentaciones.
                                </h3>

                                <p class="mt-5 text-sm leading-7 text-white/70">
                                    Encuentra agua, hielo y promociones
                                    disponibles para comprar en línea.
                                </p>
                            </div>

                            <Link
                                href="/productos"
                                class="mt-8 inline-flex h-14 items-center justify-center rounded-full bg-white px-6 text-sm font-black text-[#062A5E] transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#30BEEF] hover:text-white"
                            >
                                Ver catálogo completo →
                            </Link>
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

            <!-- FAQ -->
            <section
                id="preguntas-frecuentes"
                class="relative overflow-hidden px-4 py-16 sm:px-6 lg:px-8 lg:py-24"
            >
                <div class="absolute inset-0">
                    <picture>
                        <source
                            media="(max-width: 767px)"
                            :srcset="faqBgMobile"
                        />
                        <img
                            :src="faqBg"
                            alt=""
                            class="h-full w-full object-cover object-center"
                        />
                    </picture>

                    <div
                        class="absolute inset-0 bg-[linear-gradient(180deg,rgba(246,251,255,0.78),rgba(255,255,255,0.70))] md:bg-[linear-gradient(180deg,rgba(246,251,255,0.85),rgba(255,255,255,0.79))]"
                    />
                </div>

                <div
                    class="relative mx-auto grid w-full max-w-[1300px] gap-10 lg:grid-cols-[0.85fr_1.15fr] lg:items-start"
                >
                    <div data-reveal class="reveal">
                        <span
                            class="inline-flex rounded-full bg-white px-4 py-2 text-xs font-black uppercase tracking-[0.2em] text-[#062A5E] shadow-sm"
                        >
                            Preguntas frecuentes
                        </span>

                        <h2
                            class="mt-4 text-4xl font-black tracking-tight text-[#062A5E] md:text-6xl"
                        >
                            Resuelve tus dudas antes de comprar.
                        </h2>

                        <p
                            class="mt-5 max-w-xl text-base leading-8 text-slate-600"
                        >
                            Sabemos que antes de comprar pueden surgir dudas.
                            Aquí reunimos las preguntas más comunes para
                            ayudarte a elegir y recoger tu pedido con
                            tranquilidad.
                        </p>
                    </div>

                    <div class="space-y-3">
                        <article
                            v-for="(faq, index) in faqs"
                            :key="faq.question"
                            data-reveal
                            class="reveal overflow-hidden rounded-[28px] border border-white/70 bg-white/70 shadow-[0_18px_54px_rgba(6,42,94,0.07)] backdrop-blur-xl"
                            :class="`reveal-delay-${Math.min(index, 3)}`"
                        >
                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-4 px-5 py-5 text-left md:px-6"
                                @click="toggleFaq(index)"
                            >
                                <span
                                    class="text-lg font-black text-[#062A5E] md:text-xl"
                                >
                                    {{ faq.question }}
                                </span>

                                <span
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#eaf9ff] text-xl font-black text-[#062A5E] transition-transform duration-300 ease-out"
                                    :class="
                                        activeFaq === index
                                            ? 'rotate-45 scale-105'
                                            : 'rotate-0 scale-100'
                                    "
                                >
                                    +
                                </span>
                            </button>

                            <div
                                class="grid transition-[grid-template-rows,opacity] duration-300 ease-out"
                                :class="
                                    activeFaq === index
                                        ? 'grid-rows-[1fr] opacity-100'
                                        : 'grid-rows-[0fr] opacity-0'
                                "
                            >
                                <div class="overflow-hidden">
                                    <div class="px-5 pb-5 md:px-6">
                                        <p
                                            class="text-sm leading-7 text-slate-600"
                                        >
                                            {{ faq.answer }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <!-- CONTACTO -->
            <section
                id="contacto-home"
                class="relative overflow-hidden px-4 py-16 sm:px-6 lg:px-8 lg:py-24"
            >
                <div class="absolute inset-0">
                    <picture>
                        <source
                            media="(max-width: 767px)"
                            :srcset="heroImgMobile"
                        />
                        <img
                            :src="heroImg"
                            alt=""
                            class="h-full w-full object-cover opacity-45"
                        />
                    </picture>

                    <div
                        class="absolute inset-0 bg-[linear-gradient(135deg,rgba(6,42,94,0.92)_0%,rgba(11,95,165,0.78)_52%,rgba(48,190,239,0.66)_100%)]"
                    />
                </div>

                <div
                    class="relative mx-auto grid w-full max-w-[1500px] gap-10 rounded-[42px] border border-white/18 bg-white/10 p-6 text-white shadow-[0_30px_90px_rgba(6,42,94,0.26)] backdrop-blur-xl md:p-10 lg:grid-cols-[0.9fr_1.1fr]"
                >
                    <div data-reveal class="reveal">
                        <span
                            class="inline-flex rounded-full bg-white/12 px-4 py-2 text-xs font-black uppercase tracking-[0.2em] text-white/70"
                        >
                            Contacto
                        </span>

                        <h2
                            class="mt-4 text-4xl font-black tracking-tight md:text-6xl"
                        >
                            Quejas, dudas o sugerencias.
                        </h2>

                        <p
                            class="mt-4 max-w-xl text-base leading-8 text-white/78"
                        >
                            Escríbenos para reportar alguna situación, compartir
                            una sugerencia o solicitar apoyo relacionado con tu
                            compra.
                        </p>

                        <div
                            class="mt-8 rounded-[30px] border border-white/18 bg-white/12 p-5 shadow-[0_18px_50px_rgba(0,0,0,0.12)] backdrop-blur-2xl"
                        >
                            <p
                                class="text-sm font-black uppercase tracking-[0.18em] text-white/60"
                            >
                                Correo
                            </p>
                            <p class="mt-2 break-all text-xl font-black">
                                contactopuntopolar@gmail.com
                            </p>
                        </div>
                    </div>

                    <form
                        data-reveal
                        class="reveal reveal-delay-1 rounded-[34px] border border-white/25 bg-white/12 p-5 text-white shadow-[0_24px_70px_rgba(0,0,0,0.18)] backdrop-blur-2xl md:p-6"
                        @submit.prevent="sendContactEmail"
                    >
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label>
                                <span class="text-sm font-black text-white/85">
                                    Nombre
                                </span>
                                <input
                                    v-model="contactForm.nombre"
                                    type="text"
                                    required
                                    class="mt-2 h-12 w-full rounded-2xl border border-white/24 bg-white/14 px-4 text-sm text-white outline-none placeholder:text-white/50 transition focus:border-white/60 focus:ring-4 focus:ring-white/12"
                                />
                            </label>

                            <label>
                                <span class="text-sm font-black text-white/85">
                                    Teléfono
                                </span>
                                <input
                                    v-model="contactForm.telefono"
                                    type="text"
                                    class="mt-2 h-12 w-full rounded-2xl border border-white/24 bg-white/14 px-4 text-sm text-white outline-none placeholder:text-white/50 transition focus:border-white/60 focus:ring-4 focus:ring-white/12"
                                />
                            </label>

                            <label class="sm:col-span-2">
                                <span class="text-sm font-black text-white/85">
                                    Correo
                                </span>
                                <input
                                    v-model="contactForm.correo"
                                    type="email"
                                    class="mt-2 h-12 w-full rounded-2xl border border-white/24 bg-white/14 px-4 text-sm text-white outline-none placeholder:text-white/50 transition focus:border-white/60 focus:ring-4 focus:ring-white/12"
                                />
                            </label>

                            <label class="sm:col-span-2">
                                <span class="text-sm font-black text-white/85">
                                    Mensaje
                                </span>
                                <textarea
                                    v-model="contactForm.mensaje"
                                    rows="5"
                                    required
                                    class="mt-2 w-full rounded-2xl border border-white/24 bg-white/14 px-4 py-3 text-sm text-white outline-none placeholder:text-white/50 transition focus:border-white/60 focus:ring-4 focus:ring-white/12"
                                />
                            </label>
                        </div>

                        <button
                            type="submit"
                            class="mt-5 inline-flex h-14 w-full items-center justify-center rounded-full bg-white px-6 text-sm font-black text-[#062A5E] transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#30BEEF] hover:text-white"
                        >
                            Enviar mensaje
                        </button>

                        <p class="mt-3 text-xs leading-5 text-white/62">
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
                class="fixed bottom-4 right-4 z-50 w-[calc(100%-2rem)] max-w-md rounded-2xl border border-white/60 bg-white p-4 shadow-[0_16px_40px_rgba(6,42,94,0.15)] sm:right-4 sm:w-auto"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-[#30BEEF]/14 text-[#062A5E]"
                    >
                        ✓
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-black text-slate-900">
                            Carrito actualizado
                        </p>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ toast.text }}
                        </p>

                        <Link
                            href="/carrito"
                            class="mt-3 inline-flex h-10 items-center justify-center rounded-full bg-[#062A5E] px-5 text-xs font-black text-white transition hover:bg-[#30BEEF]"
                        >
                            Ir a carrito
                        </Link>
                    </div>
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

    .bubble {
        animation: none;
        opacity: 0;
    }
}
</style>