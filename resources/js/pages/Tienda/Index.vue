<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import HeroCarousel from '@/components/public/HeroCarousel.vue';
import ProductCard from '@/components/public/ProductCard.vue';
import ProductsShowcase from '@/components/public/ProductsShowcase.vue';
import PromotionsSection from '@/components/public/PromotionsSection.vue';
import heroImg from '@/img/hero-tienda.jpeg';
import promoBanner from '@/img/promo-registro.jpeg';
import decoDots from '@/img/web-recurso-2.png';
import decoRight from '@/img/web-recurso-3.png';
import decoLeft from '@/img/web-recurso-4.png';
import registerBar from '@/img/web-recurso-5.png';
import PublicLayout from '@/layouts/PublicLayout.vue';

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

const slides = [
    {
        id: 1,
        image: heroImg,
        badge: 'Calidad premium',
        title: props.bannerPrincipal.titulo,
        subtitle: props.bannerPrincipal.subtitulo,
        cta: props.bannerPrincipal.boton,
        href: '/productos/sartenes',
    },
    {
        id: 2,
        image: promoBanner,
        badge: 'Oferta especial',
        title: 'Cocina como experto todos los días',
        subtitle:
            'Descubre utensilios y sartenes de alto rendimiento para tu cocina.',
        cta: 'Ver promociones',
        href: '/#promocionales',
    },
];
</script>

<template>
    <PublicLayout>
        <Head title="Inicio" />

        <HeroCarousel :slides="slides" />

        <ProductsShowcase :categorias="categorias" :productos="productos" />

        <PromotionsSection />

        <section class="relative bg-[var(--brand-soft)] px-4 py-16 md:px-8">
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

            <div class="mx-auto w-full max-w-[1500px]">
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

        <section id="quienes-somos" class="bg-white py-14">
            <div
                class="mx-auto grid w-full max-w-[1500px] gap-6 px-4 md:grid-cols-2 md:px-8"
            >
                <article
                    class="rounded-3xl border border-[var(--brand-gray)] bg-[var(--brand-soft)] p-8 shadow-sm"
                >
                    <h3 class="text-3xl font-black text-neutral-900">
                        ¿Quiénes somos?
                    </h3>
                    <p class="mt-4 text-neutral-600">
                        Somos una marca mexicana especializada en productos de
                        cocina durables y modernos. Buscamos transformar tu
                        experiencia diaria con diseño, innovación y precio
                        justo.
                    </p>
                </article>

                <article
                    class="rounded-3xl border border-[var(--brand-gray)] bg-white p-8 shadow-sm transition hover:-translate-y-1 hover:shadow-xl"
                >
                    <h3 class="text-3xl font-black text-neutral-900">
                        Nuestra promesa
                    </h3>
                    <ul class="mt-4 space-y-3 text-neutral-600">
                        <li>
                            • Materiales de alta calidad para mayor durabilidad.
                        </li>
                        <li>
                            • Diseños funcionales y elegantes para cada hogar.
                        </li>
                        <li>
                            • Soporte cercano en cada una de nuestras
                            sucursales.
                        </li>
                    </ul>
                </article>
            </div>
        </section>

        <section class="bg-[var(--brand-soft)] pb-14">
            <div class="mx-auto w-full max-w-[1500px] px-4 md:px-8">
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
