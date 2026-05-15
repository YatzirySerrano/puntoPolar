<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import logo from '@/img/punto_polar_logo_navbar.svg';

const props = defineProps<{
    carritoCantidad: number;
    authUser?: {
        id: number;
        name: string;
        rol?: string;
    } | null;
}>();

const page = usePage();
const mobileOpen = ref(false);

const dashboardHref = computed(() => {
    if (!props.authUser) return '/login';

    switch (props.authUser.rol) {
        case 'admin':
        case 'vendedor':
        case 'cliente':
            return '/dashboard';
        default:
            return '/dashboard';
    }
});

const accountHref = computed(() => {
    return props.authUser ? dashboardHref.value : '/login';
});

const accountLabel = computed(() => {
    return props.authUser ? 'Mi cuenta' : 'Iniciar sesión';
});

const currentUrl = computed(() => {
    return String(page.url || '').toLowerCase();
});

const isActive = (href: string) => {
    if (href === '/') {
        return currentUrl.value === '/';
    }

    if (href.startsWith('/#')) {
        return currentUrl.value === '/';
    }

    return currentUrl.value.startsWith(href.toLowerCase());
};

const navItems = [
    {
        label: 'Inicio',
        href: '/',
        type: 'link',
    },
    {
        label: 'Productos',
        href: '/productos',
        type: 'link',
    },
    {
        label: 'Cómo comprar',
        href: '/#como-comprar',
        type: 'anchor',
    },
    {
        label: 'Filtrado',
        href: '/#proceso-filtrado',
        type: 'anchor',
    },
    {
        label: 'Preguntas',
        href: '/#preguntas-frecuentes',
        type: 'anchor',
    },
    {
        label: 'Contacto',
        href: '/#contacto-home',
        type: 'anchor',
    },
];

function toggleMobile() {
    mobileOpen.value = !mobileOpen.value;
}

function closeMobile() {
    mobileOpen.value = false;
}
</script>

<template>
    <header
        class="pointer-events-none fixed inset-x-0 top-0 z-50 px-3 pt-3 sm:px-5 sm:pt-4 lg:px-8"
    >
        <div class="mx-auto w-full max-w-[1500px]">
            <!-- Desktop -->
            <div class="hidden items-center justify-between gap-4 lg:flex">
                <div
                    class="pointer-events-auto flex items-center gap-5 rounded-full border border-white/45 bg-white/55 px-4 py-3 shadow-[0_18px_60px_rgba(6,42,94,0.12)] backdrop-blur-2xl"
                >
                    <Link
                        href="/"
                        class="group flex items-center gap-3 rounded-full pr-1"
                    >
                        <img
                            :src="logo"
                            alt="Punto Polar"
                            class="h-11 w-auto object-contain transition duration-300 group-hover:scale-[1.03]"
                        />
                    </Link>

                    <nav class="flex items-center gap-1">
                        <template
                            v-for="item in navItems"
                            :key="item.label"
                        >
                            <Link
                                v-if="item.type === 'link'"
                                :href="item.href"
                                class="rounded-full px-4 py-3 text-sm font-black transition-all duration-300 xl:px-5"
                                :class="
                                    isActive(item.href)
                                        ? 'bg-[#062A5E] text-white shadow-[0_10px_26px_rgba(6,42,94,0.18)]'
                                        : 'text-[#062A5E]/75 hover:bg-white/80 hover:text-[#062A5E]'
                                "
                            >
                                {{ item.label }}
                            </Link>

                            <a
                                v-else
                                :href="item.href"
                                class="rounded-full px-4 py-3 text-sm font-black text-[#062A5E]/75 transition-all duration-300 hover:bg-white/80 hover:text-[#062A5E] xl:px-5"
                            >
                                {{ item.label }}
                            </a>
                        </template>
                    </nav>
                </div>

                <div
                    class="pointer-events-auto flex items-center gap-2 rounded-full border border-white/45 bg-white/55 p-2 shadow-[0_18px_60px_rgba(6,42,94,0.12)] backdrop-blur-2xl"
                >
                    <Link
                        :href="accountHref"
                        class="inline-flex h-12 items-center justify-center rounded-full px-5 text-sm font-black text-[#062A5E] transition-all duration-300 hover:bg-white hover:shadow-sm"
                    >
                        {{ accountLabel }}
                    </Link>

                    <Link
                        href="/carrito"
                        class="relative inline-flex h-12 w-12 items-center justify-center rounded-full bg-white text-[#062A5E] shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#30BEEF] hover:text-white"
                        title="Carrito"
                        aria-label="Carrito"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            class="h-5 w-5 fill-none stroke-current"
                            stroke-width="1.9"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M2.25 3h1.386c.51 0 .955.343 1.087.836L5.55 7.5m0 0h12.9c.75 0 1.401.521 1.57 1.252l.798 3.454a1.125 1.125 0 0 1-1.096 1.379H7.125m-1.575-6 1.5 6m0 0a2.25 2.25 0 1 0 4.5 0m-4.5 0h4.5m6 0a2.25 2.25 0 1 0 4.5 0"
                            />
                        </svg>

                        <span
                            v-if="carritoCantidad > 0"
                            class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-[#30BEEF] px-1 text-[10px] font-black text-white shadow"
                        >
                            {{ carritoCantidad }}
                        </span>
                    </Link>

                    <Link
                        href="/productos"
                        class="inline-flex h-12 items-center justify-center rounded-full bg-[#062A5E] px-6 text-sm font-black text-white shadow-[0_14px_34px_rgba(6,42,94,0.22)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#30BEEF]"
                    >
                        Comprar ahora
                    </Link>
                </div>
            </div>

            <!-- Mobile / Tablet -->
            <div
                class="grid grid-cols-[auto_1fr_auto] items-center gap-2 lg:hidden"
            >
                <Link
                    href="/"
                    class="pointer-events-auto inline-flex h-16 items-center rounded-full border border-white/45 bg-white/55 px-5 shadow-[0_18px_45px_rgba(6,42,94,0.12)] backdrop-blur-2xl"
                    @click="closeMobile"
                >
                    <img
                        :src="logo"
                        alt="Punto Polar"
                        class="h-11 w-auto object-contain"
                    />
                </Link>

                <Link
                    href="/productos"
                    class="pointer-events-auto inline-flex h-16 items-center justify-center rounded-full border border-white/45 bg-white/55 px-5 text-base font-black text-[#062A5E] shadow-[0_18px_45px_rgba(6,42,94,0.12)] backdrop-blur-2xl transition-all duration-300 hover:bg-white"
                    @click="closeMobile"
                >
                    Comprar
                </Link>

                <button
                    type="button"
                    class="pointer-events-auto inline-flex h-16 w-16 items-center justify-center rounded-full border border-white/45 bg-white/55 text-[#062A5E] shadow-[0_18px_45px_rgba(6,42,94,0.12)] backdrop-blur-2xl transition-all duration-300 hover:bg-white"
                    :aria-expanded="mobileOpen"
                    aria-label="Abrir menú"
                    @click="toggleMobile"
                >
                    <svg
                        v-if="!mobileOpen"
                        viewBox="0 0 24 24"
                        class="h-7 w-7 fill-none stroke-current"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M5 7h14M5 12h14M5 17h14"
                        />
                    </svg>

                    <svg
                        v-else
                        viewBox="0 0 24 24"
                        class="h-7 w-7 fill-none stroke-current"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 6l12 12M18 6 6 18"
                        />
                    </svg>
                </button>
            </div>

            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 -translate-y-4 scale-[0.98]"
                enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0 scale-100"
                leave-to-class="opacity-0 -translate-y-4 scale-[0.98]"
            >
                <div
                    v-if="mobileOpen"
                    class="pointer-events-auto relative mt-3 overflow-hidden rounded-[34px] border border-white/45 bg-white/60 p-4 shadow-[0_24px_70px_rgba(6,42,94,0.18)] backdrop-blur-2xl lg:hidden"
                >
                    <div
                        class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_20%_20%,rgba(48,190,239,0.28),transparent_34%),radial-gradient(circle_at_90%_10%,rgba(6,42,94,0.18),transparent_32%),linear-gradient(180deg,rgba(255,255,255,0.55),rgba(255,255,255,0.32))]"
                    />

                    <nav class="grid gap-2">
                        <template
                            v-for="item in navItems"
                            :key="`mobile-${item.label}`"
                        >
                            <Link
                                v-if="item.type === 'link'"
                                :href="item.href"
                                class="group flex items-center justify-between rounded-[24px] px-5 py-4 text-lg font-black transition-all duration-300"
                                :class="
                                    isActive(item.href)
                                        ? 'bg-[#062A5E] text-white'
                                        : 'bg-white/55 text-[#062A5E] hover:bg-white'
                                "
                                @click="closeMobile"
                            >
                                <span>{{ item.label }}</span>
                                <span
                                    class="transition duration-300 group-hover:translate-x-1"
                                >
                                    →
                                </span>
                            </Link>

                            <a
                                v-else
                                :href="item.href"
                                class="group flex items-center justify-between rounded-[24px] bg-white/55 px-5 py-4 text-lg font-black text-[#062A5E] transition-all duration-300 hover:bg-white"
                                @click="closeMobile"
                            >
                                <span>{{ item.label }}</span>
                                <span
                                    class="transition duration-300 group-hover:translate-x-1"
                                >
                                    →
                                </span>
                            </a>
                        </template>
                    </nav>

                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                        <Link
                            href="/carrito"
                            class="relative inline-flex h-14 items-center justify-center rounded-full bg-[#062A5E] px-5 text-sm font-black text-white transition-all duration-300 hover:bg-[#30BEEF]"
                            @click="closeMobile"
                        >
                            Carrito
                            <span
                                v-if="carritoCantidad > 0"
                                class="ml-2 rounded-full bg-white px-2 py-0.5 text-xs font-black text-[#062A5E]"
                            >
                                {{ carritoCantidad }}
                            </span>
                        </Link>

                        <Link
                            :href="accountHref"
                            class="inline-flex h-14 items-center justify-center rounded-full bg-white px-5 text-sm font-black text-[#062A5E] transition-all duration-300 hover:bg-[#30BEEF] hover:text-white"
                            @click="closeMobile"
                        >
                            {{ accountLabel }}
                        </Link>
                    </div>
                </div>
            </Transition>
        </div>
    </header>
</template>

<style scoped>
@media (prefers-reduced-motion: reduce) {
    * {
        transition-duration: 0.01ms !important;
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        scroll-behavior: auto !important;
    }
}
</style>