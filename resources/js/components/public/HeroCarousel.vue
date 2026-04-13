<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';

interface Slide {
    id: number;
    image: string;
    badge: string;
    title: string;
    subtitle: string;
    cta: string;
    href: string;
}

const props = defineProps<{
    slides: Slide[];
}>();

const actual = ref(0);
let timer: ReturnType<typeof setInterval> | null = null;

const irA = (indice: number) => {
    actual.value = indice;
};

const siguiente = () => {
    actual.value = (actual.value + 1) % props.slides.length;
};

onMounted(() => {
    timer = setInterval(siguiente, 4500);
});

onBeforeUnmount(() => {
    if (timer) {
        clearInterval(timer);
    }
});
</script>

<template>
    <section class="relative overflow-hidden">
        <div class="relative h-[380px] md:h-[560px]">
            <div
                v-for="(slide, index) in slides"
                :key="slide.id"
                class="absolute inset-0 transition duration-700"
                :class="
                    index === actual
                        ? 'scale-100 opacity-100'
                        : 'scale-105 opacity-0'
                "
            >
                <img
                    :src="slide.image"
                    :alt="slide.title"
                    class="h-full w-full object-cover"
                />
                <div
                    class="absolute inset-0 bg-gradient-to-r from-black/60 via-black/25 to-black/15"
                />
            </div>

            <div
                class="absolute inset-0 mx-auto flex w-full max-w-[1500px] items-center px-4 md:px-8"
            >
                <div
                    class="relative max-w-2xl overflow-hidden rounded-[36px] border border-white/20 bg-black/35 p-8 text-white shadow-2xl backdrop-blur-md md:p-10"
                >
                    <div class="relative z-10">
                        <p
                            class="mb-4 inline-flex rounded-full border border-white/25 bg-white/10 px-4 py-1 text-xs font-bold tracking-[0.2em] text-white/90 uppercase"
                        >
                            {{ slides[actual].badge }}
                        </p>

                        <h1
                            class="text-3xl leading-tight font-black md:text-6xl"
                        >
                            {{ slides[actual].title }}
                        </h1>

                        <p
                            class="mt-4 max-w-md text-sm text-white/90 md:text-lg"
                        >
                            {{ slides[actual].subtitle }}
                        </p>

                        <div class="mt-7 flex flex-wrap gap-3">
                            <Link
                                :href="slides[actual].href"
                                class="rounded-full bg-gradient-to-r from-[var(--brand-green)] to-[var(--brand-blue)] px-8 py-3 text-sm font-black tracking-wide text-white uppercase shadow-xl transition duration-300 hover:-translate-y-1 hover:scale-[1.02]"
                            >
                                {{ slides[actual].cta }}
                            </Link>

                            <Link
                                href="/carrito"
                                class="rounded-full border border-white/35 bg-white/10 px-8 py-3 text-sm font-extrabold tracking-wide text-white uppercase transition hover:bg-white/20"
                            >
                                Ir al carrito
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <button
                type="button"
                class="absolute top-1/2 left-4 hidden h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/20 text-white backdrop-blur-sm transition hover:bg-white/30 md:flex"
                @click="irA((actual - 1 + slides.length) % slides.length)"
            >
                ‹
            </button>
            <button
                type="button"
                class="absolute top-1/2 right-4 hidden h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/20 text-white backdrop-blur-sm transition hover:bg-white/30 md:flex"
                @click="siguiente"
            >
                ›
            </button>

            <div
                class="absolute bottom-5 left-1/2 flex -translate-x-1/2 items-center gap-2 rounded-full bg-black/30 px-3 py-2 backdrop-blur-sm"
            >
                <button
                    v-for="(slide, index) in slides"
                    :key="slide.id"
                    type="button"
                    class="h-2.5 rounded-full transition"
                    :class="
                        index === actual
                            ? 'w-6 bg-white'
                            : 'w-2.5 bg-white/60 hover:bg-white'
                    "
                    @click="irA(index)"
                />
            </div>
        </div>
    </section>
</template>
