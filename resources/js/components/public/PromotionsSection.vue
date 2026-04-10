<script setup lang="ts">
import promoBanner from '@/img/promo-registro.jpeg';

const promos = [
    {
        title: '10% en tu primera compra',
        description: 'Regístrate y recibe un cupón de bienvenida en minutos.',
        cta: 'Quiero mi cupón',
    },
    {
        title: 'Envío gratis desde $1,299',
        description: 'Aprovecha envío nacional sin costo por tiempo limitado.',
        cta: 'Ver condiciones',
    },
    {
        title: 'Meses sin intereses',
        description:
            'Compra tus productos favoritos a 3 y 6 MSI participantes.',
        cta: 'Conocer bancos',
    },
];
</script>

<template>
    <section id="promocionales" class="bg-[var(--brand-soft)] py-10">
        <div class="mx-auto w-full max-w-[1500px] space-y-6 px-4 md:px-8">
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

            <div class="grid gap-4 md:grid-cols-3">
                <article
                    v-for="promo in promos"
                    :key="promo.title"
                    class="group relative overflow-hidden rounded-3xl border border-[var(--brand-gray)] bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1.5 hover:shadow-xl"
                >
                    <div
                        class="absolute -top-10 -right-10 h-24 w-24 rounded-full bg-gradient-to-br from-[var(--brand-green)] to-[var(--brand-blue)] opacity-15 transition duration-500 group-hover:scale-125"
                    />
                    <h3 class="relative text-2xl font-black text-neutral-900">
                        {{ promo.title }}
                    </h3>
                    <p class="relative mt-2 text-sm text-neutral-600">
                        {{ promo.description }}
                    </p>
                    <button
                        type="button"
                        class="relative mt-5 rounded-full border border-[var(--brand-blue)] px-5 py-2 text-sm font-bold text-[var(--brand-blue)] transition hover:bg-[var(--brand-blue)] hover:text-white"
                    >
                        {{ promo.cta }}
                    </button>
                </article>
            </div>
        </div>
    </section>
</template>
