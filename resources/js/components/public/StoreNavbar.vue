<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import logo from '@/img/logo.png';

defineProps<{
    carritoCantidad: number;
    authUser?: {
        id: number;
        name: string;
        rol?: string;
    } | null;
}>();
</script>

<template>
    <header
        class="z-40 border-b border-[var(--brand-gray)]/70 bg-white/95 backdrop-blur-md"
    >
        <div class="border-b border-[var(--brand-gray)]/40">
            <div
                class="mx-auto flex w-full max-w-[1500px] items-center justify-between px-4 py-3 text-[11px] font-semibold tracking-[0.18em] text-neutral-500 uppercase md:px-8"
            >
                <nav class="flex items-center gap-5">
                    <Link
                        href="/"
                        class="transition hover:text-[var(--brand-blue)]"
                        >Inicio</Link
                    >
                    <a
                        href="#quienes-somos"
                        class="transition hover:text-[var(--brand-blue)]"
                        >Quiénes somos</a
                    >
                    <a
                        href="#sucursales"
                        class="transition hover:text-[var(--brand-blue)]"
                        >Sucursales</a
                    >
                    <a
                        href="#promocionales"
                        class="transition hover:text-[var(--brand-blue)]"
                        >Promocionales</a
                    >
                    <a
                        href="#contacto"
                        class="transition hover:text-[var(--brand-blue)]"
                        >Contacto</a
                    >
                </nav>

                <div class="hidden items-center gap-5 md:flex">
                    <Link
                        href="/carrito"
                        class="transition hover:text-[var(--brand-blue)]"
                        >Carrito</Link
                    >
                    <Link
                        :href="authUser ? '/dashboard' : '/login'"
                        class="rounded-full border px-3 py-1 text-[10px] font-bold transition hover:border-[var(--brand-blue)] hover:text-[var(--brand-blue)]"
                    >
                        {{
                            authUser
                                ? `Hola, ${authUser.name}`
                                : 'Iniciar sesión'
                        }}
                    </Link>
                </div>
            </div>
        </div>

        <div
            class="mx-auto flex w-full max-w-[1500px] flex-col gap-4 px-4 py-5 md:flex-row md:items-center md:justify-between md:px-8"
        >
            <Link href="/" class="group shrink-0">
                <img
                    :src="logo"
                    alt="Mr. Lana"
                    class="h-14 w-auto object-contain transition duration-300 group-hover:scale-[1.03]"
                />
            </Link>

            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="hidden rounded-full border border-[var(--brand-gray)] bg-white px-5 py-2 text-xs font-bold tracking-wide text-neutral-500 uppercase transition hover:border-[var(--brand-blue)] hover:text-[var(--brand-blue)] md:inline-flex"
                >
                    Wishlist (3)
                </button>

                <Link
                    href="/carrito"
                    class="relative flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-[var(--brand-gray)] bg-white text-[var(--brand-green)] shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-[var(--brand-green)] hover:shadow-lg"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M2.25 3h1.386c.51 0 .955.343 1.087.836L5.55 7.5m0 0h12.9c.75 0 1.401.521 1.57 1.252l.798 3.454a1.125 1.125 0 0 1-1.096 1.379H7.125m-1.575-6 1.5 6m0 0a2.25 2.25 0 1 0 4.5 0m-4.5 0h4.5m6 0a2.25 2.25 0 1 0 4.5 0"
                        />
                    </svg>

                    <span
                        v-if="carritoCantidad > 0"
                        class="absolute -top-1 -right-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-[var(--brand-green)] px-1 text-[10px] font-bold text-white shadow"
                    >
                        {{ carritoCantidad }}
                    </span>
                </Link>
            </div>
        </div>
    </header>
</template>
