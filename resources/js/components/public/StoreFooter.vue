<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import logo from '@/img/punto_polar_logo_transparente.svg'

type Active = 'inicio' | '';

type NavItem = {
    key: Active;
    href: string;
    label: string;
};

const page = usePage();
const year = new Date().getFullYear();

const phoneRaw = '7771148125';
const phonePretty = '777 114 8125';
const whatsappUrl = `https://wa.me/52${phoneRaw}`;

const address = 'Privada Chilpancingo #20, Vicente Guerrero, Jiutepec, Mor.';
const reference = 'Frente a Capilla San Mateo';

const mapsUrl = 'https://maps.app.goo.gl/PXmUr6LGAF76msMc8';

const mapsEmbedUrl =
    'https://www.google.com/maps?q=Privada%20Chilpancingo%2020%20Vicente%20Guerrero%20Jiutepec%20Morelos&output=embed';

const activeKey = computed<Active>(() => {
    const url = String(page.url || '').toLowerCase();

    if (url === '/' || url.startsWith('/index')) return 'inicio';

    return '';
});

const isActive = (key: Active, href: string) => {
    const url = String(page.url || '').toLowerCase();

    if (key) return key === activeKey.value;

    return href !== '/' ? url.startsWith(href) : url === '/';
};

const siteLinks = computed<NavItem[]>(() => [
    {
        key: 'inicio',
        label: 'Inicio',
        href: '/',
    },
    {
        key: '',
        label: 'Productos',
        href: '/productos',
    },
    {
        key: '',
        label: 'Cómo comprar',
        href: '/#como-comprar',
    },
    {
        key: '',
        label: 'Filtrado',
        href: '/#filtrado',
    },
    {
        key: '',
        label: 'Preguntas frecuentes',
        href: '/#faq',
    },
]);

const legalLinks = [
    {
        label: 'Términos y condiciones',
        href: '/terminos-y-condiciones',
    },
    {
        label: 'Aviso de privacidad',
        href: '/aviso-de-privacidad',
    },
];

const productLinks = [
    {
        label: 'Agua purificada',
        href: '/productos',
    },
    {
        label: 'Hielo',
        href: '/productos',
    },
    {
        label: 'Promociones',
        href: '/productos',
    },
];

const socialLinks = [
    {
        label: 'Facebook',
        href: 'https://www.facebook.com/profile.php?id=61578482788839&locale=es_LA',
        icon: 'facebook',
    },
    {
        label: 'Instagram',
        href: 'https://www.instagram.com/puntopolar.hieloagua/',
        icon: 'instagram',
    },
    {
        label: 'TikTok',
        href: 'https://www.tiktok.com/@punto.polar.hielo?is_from_webapp=1&sender_device=pc',
        icon: 'tiktok',
    },
];
</script>

<template>
    <footer
        class="relative overflow-hidden bg-[linear-gradient(135deg,#ffffff_0%,#eaf9ff_34%,#d9f5ff_62%,#ffffff_100%)] text-slate-950"
        role="contentinfo"
    >
        <div
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_10%_10%,rgba(48,190,239,0.22),transparent_34%),radial-gradient(circle_at_86%_18%,rgba(6,42,94,0.12),transparent_30%),radial-gradient(circle_at_50%_100%,rgba(48,190,239,0.12),transparent_36%)]"
        />

        <section id="contacto-home" class="relative px-4 py-10 sm:px-6 lg:px-8 lg:py-12">
            <div class="mx-auto w-full max-w-[1320px]">
                <div
                    class="grid gap-8 lg:grid-cols-[minmax(0,0.95fr)_minmax(380px,0.95fr)] lg:gap-10"
                >
                    <div class="space-y-6">
                        <div>
                            <Link href="/" class="inline-flex items-center">
                                <img
                                    :src="logo"
                                    alt="Punto Polar"
                                    class="h-20 w-auto object-contain drop-shadow-[0_14px_30px_rgba(6,42,94,0.14)] sm:h-24 lg:h-28"
                                />
                            </Link>

                            <p
                                class="mt-4 max-w-xl text-sm leading-7 text-slate-700 md:text-base"
                            >
                                Agua purificada y hielo disponibles 24/7. Compra
                                en línea con pago anticipado y recoge en nuestra
                                ubicación.
                            </p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-center">
                            <a
                                :href="whatsappUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="group inline-flex items-center gap-4 rounded-[26px] bg-white/25 px-2 py-2 transition-all duration-300 hover:-translate-y-0.5 hover:bg-white/55"
                                aria-label="Contactar por WhatsApp"
                            >
                                <span
                                    class="flex h-14 w-14 shrink-0 items-center justify-center rounded-[22px] bg-white/45 text-black shadow-[0_12px_26px_rgba(6,42,94,0.10)] backdrop-blur-xl transition group-hover:bg-white"
                                >
                                    <svg
                                        viewBox="0 0 24 24"
                                        class="h-7 w-7 fill-current"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M20.52 3.48A11.84 11.84 0 0 0 12.1 0C5.55 0 .22 5.33.22 11.88c0 2.1.55 4.14 1.6 5.94L0 24l6.32-1.66a11.86 11.86 0 0 0 5.78 1.47h.01c6.55 0 11.88-5.33 11.88-11.88 0-3.18-1.24-6.16-3.47-8.45ZM12.1 21.8h-.01a9.86 9.86 0 0 1-5.03-1.38l-.36-.22-3.75.98 1-3.65-.24-.38a9.82 9.82 0 0 1-1.5-5.26c0-5.45 4.44-9.88 9.9-9.88 2.64 0 5.12 1.03 6.98 2.9a9.78 9.78 0 0 1 2.9 6.98c0 5.46-4.44 9.9-9.89 9.9Zm5.42-7.4c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.95 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.8-1.49-1.78-1.66-2.08-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.38-.02-.53-.08-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.38-.27.3-1.05 1.03-1.05 2.5s1.08 2.9 1.23 3.1c.15.2 2.12 3.23 5.13 4.52.72.31 1.28.5 1.72.64.72.23 1.38.2 1.9.12.58-.09 1.76-.72 2-1.42.25-.7.25-1.3.18-1.42-.08-.13-.27-.2-.57-.35Z"
                                        />
                                    </svg>
                                </span>

                                <span>
                                    <span
                                        class="block text-[11px] font-black uppercase tracking-[0.22em] text-slate-500"
                                    >
                                        WhatsApp
                                    </span>
                                    <span
                                        class="mt-1 block text-xl font-black tracking-tight text-[#062A5E] sm:text-2xl"
                                    >
                                        {{ phonePretty }}
                                    </span>
                                </span>
                            </a>

                            <div class="flex items-center gap-2 sm:justify-end">
                                <a
                                    v-for="social in socialLinks"
                                    :key="social.label"
                                    :href="social.href"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="group flex h-14 w-14 items-center justify-center rounded-[22px] bg-white/30 text-black shadow-[0_12px_26px_rgba(6,42,94,0.10)] backdrop-blur-xl transition-all duration-300 hover:-translate-y-0.5 hover:bg-white"
                                    :aria-label="social.label"
                                >
                                    <svg
                                        v-if="social.icon === 'facebook'"
                                        viewBox="0 0 24 24"
                                        class="h-7 w-7 fill-current"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M13.5 22v-8h2.68l.42-3.11h-3.1V8.9c0-.9.25-1.51 1.55-1.51h1.66V4.61c-.29-.04-1.27-.12-2.42-.12-2.4 0-4.04 1.46-4.04 4.15v2.25H8.04V14h2.71v8h2.75Z"
                                        />
                                    </svg>

                                    <svg
                                        v-else-if="social.icon === 'instagram'"
                                        viewBox="0 0 24 24"
                                        class="h-7 w-7 fill-none stroke-current"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        aria-hidden="true"
                                    >
                                        <rect
                                            x="3"
                                            y="3"
                                            width="18"
                                            height="18"
                                            rx="5"
                                        />
                                        <circle cx="12" cy="12" r="4" />
                                        <path d="M17.5 6.5h.01" />
                                    </svg>

                                    <svg
                                        v-else
                                        viewBox="0 0 24 24"
                                        class="h-7 w-7 fill-current"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M16.58 2c.34 2.42 1.7 3.86 4.08 4.01v3.03c-1.38.13-2.59-.31-3.99-1.16v5.67c0 7.2-7.84 9.45-10.99 4.29-2.02-3.32-.78-9.14 5.7-9.37v3.2c-.45.07-.93.18-1.37.33-1.31.44-2.05 1.27-1.85 2.73.39 2.78 5.48 3.61 5.06-1.83V2h3.36Z"
                                        />
                                    </svg>
                                </a>
                            </div>
                        </div>

                        <div
                            class="grid gap-6 pt-1 sm:grid-cols-3 lg:max-w-3xl"
                        >
                            <nav aria-label="Enlaces del sitio">
                                <p
                                    class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-500"
                                >
                                    Navegación
                                </p>

                                <ul class="mt-3 space-y-1.5">
                                    <li
                                        v-for="link in siteLinks"
                                        :key="link.href"
                                    >
                                        <Link
                                            :href="link.href"
                                            class="group inline-flex items-center gap-2 text-sm font-bold text-slate-700 transition hover:text-[#062A5E]"
                                            :class="
                                                isActive(link.key, link.href)
                                                    ? 'text-[#062A5E]'
                                                    : ''
                                            "
                                        >
                                            <span
                                                class="h-1.5 w-1.5 rounded-full bg-[#30BEEF] opacity-60 transition group-hover:opacity-100"
                                            />
                                            {{ link.label }}
                                        </Link>
                                    </li>
                                </ul>
                            </nav>

                            <nav aria-label="Productos">
                                <p
                                    class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-500"
                                >
                                    Productos
                                </p>

                                <ul class="mt-3 space-y-1.5">
                                    <li
                                        v-for="link in productLinks"
                                        :key="link.label"
                                    >
                                        <Link
                                            :href="link.href"
                                            class="group inline-flex items-center gap-2 text-sm font-bold text-slate-700 transition hover:text-[#062A5E]"
                                        >
                                            <span
                                                class="h-1.5 w-1.5 rounded-full bg-[#30BEEF] opacity-60 transition group-hover:opacity-100"
                                            />
                                            {{ link.label }}
                                        </Link>
                                    </li>
                                </ul>
                            </nav>

                            <nav aria-label="Legal">
                                <p
                                    class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-500"
                                >
                                    Legal
                                </p>

                                <ul class="mt-3 space-y-1.5">
                                    <li
                                        v-for="link in legalLinks"
                                        :key="link.href"
                                    >
                                        <Link
                                            :href="link.href"
                                            class="group inline-flex items-center gap-2 text-sm font-bold text-slate-700 transition hover:text-[#062A5E]"
                                        >
                                            <span
                                                class="h-1.5 w-1.5 rounded-full bg-[#30BEEF] opacity-60 transition group-hover:opacity-100"
                                            />
                                            {{ link.label }}
                                        </Link>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <p
                                class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-500"
                            >
                                Ubicación
                            </p>

                            <h3
                                class="mt-2 text-2xl font-black tracking-tight text-[#062A5E] md:text-3xl"
                            >
                                Encuéntranos en Jiutepec
                            </h3>

                            <p class="mt-2 max-w-xl text-sm leading-6 text-slate-700 md:text-base">
                                {{ address }}<br />
                                {{ reference }}
                            </p>
                        </div>

                        <div
                            class="group relative overflow-hidden rounded-[30px] bg-white/30 p-2 shadow-[0_20px_54px_rgba(6,42,94,0.14)] backdrop-blur-xl transition-all duration-300 hover:-translate-y-0.5 hover:bg-white/50"
                        >
                            <div
                                class="relative h-[240px] overflow-hidden rounded-[24px] bg-slate-200 sm:h-[280px] lg:h-[320px]"
                            >
                                <iframe
                                    :src="mapsEmbedUrl"
                                    class="absolute inset-0 h-full w-full border-0"
                                    loading="lazy"
                                    allowfullscreen
                                    referrerpolicy="no-referrer-when-downgrade"
                                    title="Ubicación de Punto Polar"
                                />

                                <div
                                    class="pointer-events-none absolute inset-x-0 bottom-0 z-10 bg-gradient-to-t from-[#062A5E]/75 to-transparent p-4"
                                />

                                <a
                                    :href="mapsUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="absolute right-4 bottom-4 z-20 inline-flex rounded-full bg-white px-4 py-2.5 text-xs font-black text-[#062A5E] shadow-lg transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#30BEEF] hover:text-white sm:text-sm"
                                >
                                    Abrir en Google Maps →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="mt-9 flex flex-col gap-2 border-t border-slate-900/10 pt-5 text-sm font-semibold text-slate-600 md:flex-row md:items-center md:justify-between"
                >
                    <p>
                        © {{ year }} Punto Polar. Todos los derechos reservados.
                    </p>

                    <p>Agua purificada y hielo 24/7.</p>
                </div>
            </div>
        </section>
    </footer>
</template>