<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
import ProductCard from '@/components/public/ProductCard.vue';
import heroImg from '@/img/hero-tienda.jpeg';
import promoBanner from '@/img/promo-registro.jpeg';
import sartenesTitle from '@/img/sartenes-title.jpeg';
import decoBanner from '@/img/web-recurso-1.png';
import decoDots from '@/img/web-recurso-2.png';
import decoLeft from '@/img/web-recurso-4.png';
import decoRight from '@/img/web-recurso-3.png';
import registerBar from '@/img/web-recurso-5.png';

interface Categoria {
    id: number;
    nombre: string;
    slug: string;
    imagen?: string | null;
}

interface Producto {
    id: number;
    nombre: string;
    slug: string;
    sku: string;
    descripcion?: string | null;
    precio: number | string;
    precio_comparacion?: number | string | null;
    stock: number;
    imagen_principal?: string | null;
    destacado?: boolean;
    categoria_id?: number | null;
    categoria?: Categoria | null;
}

const props = defineProps<{
    categorias: Categoria[];
    destacados: Producto[];
    productos: Producto[];
    bannerPrincipal: {
        titulo: string;
        subtitulo: string;
        boton: string;
    };
}>();

const categoriaActiva = ref<string>('todas');
const busqueda = ref('');

const normalizar = (valor: string | null | undefined) =>
    (valor ?? '').toLowerCase().trim();

const productosFiltrados = computed(() => {
    const txt = normalizar(busqueda.value);

    return props.productos.filter((producto) => {
        const coincideCategoria =
            categoriaActiva.value === 'todas' ||
            producto.categoria?.slug === categoriaActiva.value ||
            props.categorias.find((cat) => cat.id === producto.categoria_id)
                ?.slug === categoriaActiva.value;

        const coincideBusqueda =
            !txt ||
            normalizar(producto.nombre).includes(txt) ||
            normalizar(producto.descripcion).includes(txt) ||
            normalizar(producto.sku).includes(txt);

        return coincideCategoria && coincideBusqueda;
    });
});
</script>

<template>
    <PublicLayout>
        <Head title="Inicio" />

        <section class="relative overflow-hidden">
            <img
                :src="heroImg"
                alt="Banner principal"
                class="h-[360px] w-full object-cover md:h-[520px]"
            />
            <div
                class="absolute inset-0 bg-gradient-to-r from-black/55 via-black/20 to-black/15"
            />

            <div
                class="absolute inset-0 mx-auto flex max-w-7xl items-center px-4 md:px-6"
            >
                <div
                    class="relative max-w-xl overflow-hidden rounded-[36px] border border-white/20 bg-black/35 p-8 text-white shadow-2xl backdrop-blur-md md:p-10"
                >
                    <img
                        :src="decoBanner"
                        alt=""
                        class="pointer-events-none absolute inset-0 h-full w-full object-cover opacity-30"
                    />

                    <div class="relative z-10">
                        <p
                            class="mb-4 inline-flex rounded-full border border-white/25 bg-white/10 px-4 py-1 text-xs font-bold tracking-[0.2em] text-white/90 uppercase"
                        >
                            Calidad premium
                        </p>

                        <h1
                            class="text-3xl leading-tight font-black md:text-6xl"
                        >
                            {{ bannerPrincipal.titulo }}
                        </h1>

                        <p
                            class="mt-4 max-w-md text-sm text-white/90 md:text-lg"
                        >
                            {{ bannerPrincipal.subtitulo }}
                        </p>

                        <div class="mt-7 flex flex-wrap gap-3">
                            <button
                                class="rounded-full bg-gradient-to-r from-[var(--brand-green)] to-[var(--brand-blue)] px-8 py-3 text-sm font-black tracking-wide text-white uppercase shadow-xl transition duration-300 hover:-translate-y-1 hover:scale-[1.02]"
                            >
                                {{ bannerPrincipal.boton }}
                            </button>

                            <Link
                                href="/productos/sartenes"
                                class="rounded-full border border-white/35 bg-white/10 px-8 py-3 text-sm font-extrabold tracking-wide text-white uppercase transition hover:bg-white/20"
                            >
                                Ver catálogo
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="absolute bottom-5 left-1/2 flex -translate-x-1/2 items-center gap-2 rounded-full bg-black/30 px-3 py-2 backdrop-blur-sm"
            >
                <span class="h-2.5 w-6 rounded-full bg-white shadow" />
                <span
                    class="h-2.5 w-2.5 rounded-full bg-white/70 shadow transition hover:bg-white"
                />
                <span
                    class="h-2.5 w-2.5 rounded-full bg-white/50 shadow transition hover:bg-white"
                />
            </div>
        </section>

        <section class="bg-[var(--brand-soft)]">
            <div
                class="mx-auto grid max-w-7xl gap-8 px-4 py-14 md:grid-cols-[280px_1fr] md:px-6"
            >
                <aside
                    class="rounded-[30px] border border-[var(--brand-gray)]/70 bg-white p-5 shadow-sm"
                >
                    <h2 class="mb-5 text-4xl font-black text-neutral-900">
                        Productos
                    </h2>

                    <div class="mb-5">
                        <input
                            v-model="busqueda"
                            type="text"
                            placeholder="Buscar por nombre o SKU"
                            class="h-12 w-full rounded-2xl border border-[var(--brand-gray)] bg-white px-4 text-sm transition outline-none focus:border-[var(--brand-blue)] focus:shadow-[0_0_0_4px_rgba(48,190,239,0.15)]"
                        />
                    </div>

                    <div class="space-y-2">
                        <button
                            type="button"
                            class="group flex w-full items-center justify-between rounded-2xl px-4 py-3 text-left font-extrabold transition duration-300"
                            :class="
                                categoriaActiva === 'todas'
                                    ? 'bg-[var(--brand-blue)] text-white shadow-lg'
                                    : 'bg-[var(--brand-soft)] text-neutral-700 hover:bg-white hover:shadow-md'
                            "
                            @click="categoriaActiva = 'todas'"
                        >
                            <span>Todas</span>
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-[var(--brand-green)]"
                            />
                        </button>

                        <button
                            v-for="categoria in categorias"
                            :key="categoria.id"
                            type="button"
                            class="group flex w-full items-center justify-between rounded-2xl px-4 py-3 text-left font-extrabold transition duration-300"
                            :class="
                                categoriaActiva === categoria.slug
                                    ? 'bg-[var(--brand-blue)] text-white shadow-lg'
                                    : 'bg-[var(--brand-soft)] text-neutral-700 hover:bg-white hover:shadow-md'
                            "
                            @click="categoriaActiva = categoria.slug"
                        >
                            <span>{{ categoria.nombre }}</span>
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-[var(--brand-green)] shadow-[0_0_10px_rgba(125,208,60,0.4)]"
                            />
                        </button>
                    </div>
                </aside>

                <div class="space-y-10">
                    <div
                        class="flex flex-wrap items-center justify-between gap-4"
                    >
                        <img
                            :src="sartenesTitle"
                            alt="Sartenes"
                            class="h-16 w-auto drop-shadow-[0_8px_18px_rgba(48,190,239,0.2)] md:h-20"
                        />
                        <p
                            class="rounded-full bg-white px-5 py-2 text-sm font-bold text-neutral-500 shadow-sm"
                        >
                            Mostrando
                            {{ productosFiltrados.length }} producto(s)
                        </p>
                    </div>

                    <div
                        v-if="productosFiltrados.length"
                        class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4"
                    >
                        <ProductCard
                            v-for="producto in productosFiltrados"
                            :key="producto.id"
                            :producto="producto"
                        />
                    </div>

                    <div
                        v-else
                        class="rounded-3xl border border-dashed border-[var(--brand-gray)] bg-white p-10 text-center"
                    >
                        <h3 class="text-xl font-black text-neutral-900">
                            No encontramos productos
                        </h3>
                        <p class="mt-2 text-neutral-500">
                            Intenta con otra categoría o cambia tu búsqueda.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-[var(--brand-soft)] pb-8">
            <div class="mx-auto max-w-7xl px-4 md:px-6">
                <div
                    class="group relative overflow-hidden rounded-[34px] shadow-[0_14px_40px_rgba(0,0,0,0.08)]"
                >
                    <img
                        :src="promoBanner"
                        alt="Promoción de registro"
                        class="h-auto w-full object-cover transition duration-700 group-hover:scale-[1.03]"
                    />
                    <div
                        class="absolute inset-0 bg-gradient-to-r from-black/15 to-transparent"
                    />
                </div>
            </div>
        </section>

        <section class="relative bg-[var(--brand-soft)] px-4 py-16 md:px-6">
            <img
                :src="decoLeft"
                alt=""
                class="pointer-events-none absolute top-1/2 left-0 hidden w-20 -translate-y-1/2 md:block"
            />
            <img
                :src="decoRight"
                alt=""
                class="pointer-events-none absolute top-1/2 right-0 hidden w-20 -translate-y-1/2 md:block"
            />

            <div class="mx-auto max-w-7xl">
                <div class="mb-12 text-center">
                    <h2
                        class="text-4xl font-black tracking-tight text-[var(--brand-green)] uppercase drop-shadow-[0_5px_10px_rgba(125,208,60,0.14)] md:text-6xl"
                    >
                        Productos destacados
                    </h2>
                </div>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <ProductCard
                        v-for="producto in destacados"
                        :key="producto.id"
                        :producto="producto"
                    />
                </div>
            </div>
        </section>

        <section class="bg-[var(--brand-soft)] pb-14">
            <div class="mx-auto max-w-7xl px-4 md:px-6">
                <div
                    class="overflow-hidden rounded-[40px] bg-white shadow-[0_18px_40px_rgba(0,0,0,0.06)]"
                >
                    <div
                        class="relative grid gap-6 p-6 md:grid-cols-[1fr_2fr] md:items-center md:p-8"
                    >
                        <img
                            :src="registerBar"
                            alt=""
                            class="pointer-events-none absolute inset-x-6 top-6 hidden h-16 w-[calc(100%-3rem)] object-cover opacity-15 md:block"
                        />
                        <img
                            :src="decoDots"
                            alt=""
                            class="pointer-events-none absolute top-0 right-0 hidden h-full w-full object-cover opacity-10 md:block"
                        />

                        <div class="relative z-10">
                            <h3 class="text-3xl font-black text-neutral-900">
                                Regístrate
                            </h3>
                            <p class="text-neutral-600">
                                Para recibir las mejores ofertas
                            </p>
                        </div>

                        <div class="relative z-10">
                            <div class="flex flex-col gap-3 md:flex-row">
                                <input
                                    type="email"
                                    placeholder="Ingresa tu correo aquí"
                                    class="h-14 flex-1 rounded-full border border-[var(--brand-gray)] bg-[#f7f7f7] px-6 transition outline-none focus:border-[var(--brand-blue)] focus:bg-white focus:shadow-[0_0_0_4px_rgba(48,190,239,0.12)]"
                                />
                                <button
                                    type="button"
                                    class="h-14 rounded-full bg-gradient-to-r from-[var(--brand-green)] to-[var(--brand-blue)] px-9 font-black text-white shadow-md transition duration-300 hover:-translate-y-0.5 hover:scale-[1.01] hover:shadow-xl"
                                >
                                    ¡Regístrate!
                                </button>
                            </div>

                            <p class="mt-4 text-xs text-neutral-400">
                                Al registrarme, acepto que mis datos sean
                                tratados para fines mercadotécnicos de acuerdo
                                al Aviso de Privacidad.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
