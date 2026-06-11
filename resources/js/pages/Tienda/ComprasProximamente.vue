<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import PublicLayout from '@/layouts/PublicLayout.vue'
import logo from '@/img/punto_polar_logo_transparente.svg'

const waUrl =
    'https://wa.me/527771148125?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20los%20productos%20y%20servicios%20de%20Punto%20Polar.'
const phonePretty = '777 114 8125'
</script>

<template>
    <PublicLayout>
        <Head title="Compras en línea próximamente · Punto Polar" />

        <section
            class="relative min-h-[calc(100vh-80px)] overflow-hidden bg-[linear-gradient(135deg,#f6fbff_0%,#eaf9ff_50%,#f6fbff_100%)] px-4 py-24 sm:px-6 lg:px-8"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_18%_18%,rgba(48,190,239,0.20),transparent_38%),radial-gradient(circle_at_82%_80%,rgba(6,42,94,0.10),transparent_36%),radial-gradient(circle_at_50%_50%,rgba(125,208,60,0.06),transparent_50%)]"
            />

            <div
                class="relative mx-auto flex min-h-[calc(100vh-200px)] max-w-3xl flex-col items-center justify-center text-center"
            >
                <img
                    :src="logo"
                    alt="Punto Polar"
                    class="pp-entrance pp-entrance-delay-1 mb-10 h-24 w-auto object-contain opacity-90 drop-shadow-[0_14px_30px_rgba(6,42,94,0.12)] sm:h-28 lg:h-32"
                />

                <span
                    class="pp-entrance pp-entrance-delay-2 inline-flex items-center gap-2.5 rounded-full border border-[#30BEEF]/30 bg-[#30BEEF]/10 px-5 py-2.5 text-xs font-black uppercase tracking-[0.22em] text-[#062A5E]"
                >
                    <span
                        class="h-2 w-2 animate-pulse rounded-full bg-[#30BEEF]"
                    />
                    Próximamente
                </span>

                <h1
                    class="pp-entrance pp-entrance-delay-3 mt-7 text-4xl font-black leading-tight tracking-tight text-[#062A5E] sm:text-5xl lg:text-6xl"
                >
                    Compras en línea próximamente
                </h1>

                <p
                    class="pp-entrance pp-entrance-delay-4 mt-5 max-w-2xl text-base leading-8 text-[#062A5E]/70 sm:text-lg"
                >
                    Estamos preparando una mejor experiencia para que puedas
                    comprar agua y hielo en línea.
                </p>

                <div class="pp-entrance pp-entrance-delay-4 my-8 h-px w-20 rounded-full bg-[#30BEEF]/40" />

                <p
                    class="pp-entrance pp-entrance-delay-4 max-w-xl text-sm leading-7 text-slate-600 sm:text-base"
                >
                    Por el momento, los pedidos y cotizaciones se estarán
                    atendiendo directamente por WhatsApp. Escríbenos y con gusto
                    te ayudamos con disponibilidad, precios, pedidos o cualquier
                    servicio relacionado con Punto Polar.
                </p>

                <a
                    :href="waUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="pp-entrance pp-entrance-delay-5 mt-10 inline-flex h-16 items-center gap-4 rounded-full bg-[#25D366] px-8 text-base font-black text-white shadow-[0_18px_45px_rgba(37,211,102,0.30)] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_24px_55px_rgba(37,211,102,0.42)]"
                >
                    <svg
                        viewBox="0 0 32 32"
                        class="h-7 w-7 fill-current"
                        aria-hidden="true"
                    >
                        <path
                            d="M16.04 3C9.39 3 4 8.27 4 14.78c0 2.5.82 4.82 2.22 6.73L4.77 29l7.71-2.02a12.35 12.35 0 0 0 3.56.52C22.69 27.5 28 22.23 28 15.72S22.69 3 16.04 3Zm0 22.34c-1.12 0-2.22-.18-3.26-.55l-.47-.17-4.58 1.2.87-4.43-.29-.45a10.1 10.1 0 0 1-1.62-5.48c0-5.3 4.39-9.62 9.8-9.62s9.8 4.32 9.8 9.62-4.39 9.88-9.8 9.88Zm5.36-7.35c-.29-.15-1.73-.84-2-.94-.27-.1-.47-.15-.67.15-.2.29-.77.94-.95 1.13-.17.2-.35.22-.64.07-.29-.15-1.22-.44-2.32-1.4-.86-.75-1.44-1.68-1.61-1.97-.17-.29-.02-.45.13-.59.13-.13.29-.35.44-.52.15-.17.2-.29.29-.49.1-.2.05-.37-.02-.52-.07-.15-.67-1.59-.92-2.18-.24-.57-.49-.49-.67-.5h-.57c-.2 0-.52.07-.79.37-.27.29-1.04 1-1.04 2.44 0 1.44 1.07 2.83 1.22 3.03.15.2 2.1 3.15 5.1 4.42.71.3 1.27.48 1.7.61.71.22 1.36.19 1.87.12.57-.08 1.73-.7 1.98-1.37.24-.67.24-1.24.17-1.37-.07-.12-.27-.2-.56-.35Z"
                        />
                    </svg>
                    Contactar por WhatsApp
                </a>

                <a
                    :href="waUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="pp-entrance pp-entrance-delay-5 mt-4 inline-flex items-center gap-3 rounded-full border border-[#062A5E]/12 bg-white px-6 py-3.5 text-lg font-black text-[#062A5E] shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-[#30BEEF] hover:shadow-md"
                    :aria-label="`Llamar o enviar mensaje al ${phonePretty}`"
                >
                    <svg
                        viewBox="0 0 24 24"
                        class="h-5 w-5 fill-none"
                        stroke="#30BEEF"
                        stroke-width="2"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"
                        />
                    </svg>
                    {{ phonePretty }}
                </a>

                <Link
                    href="/"
                    class="pp-entrance pp-entrance-delay-6 mt-8 inline-flex items-center gap-2 text-sm font-bold text-[#062A5E]/50 transition-all duration-300 hover:text-[#062A5E]"
                >
                    ← Volver al inicio
                </Link>
            </div>
        </section>
    </PublicLayout>
</template>
