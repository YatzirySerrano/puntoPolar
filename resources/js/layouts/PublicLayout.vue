<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { computed, onMounted } from 'vue';
import StoreFooter from '@/components/public/StoreFooter.vue';
import StoreNavbar from '@/components/public/StoreNavbar.vue';
//import WhaticketWidget from '@/components/integrations/WhaticketWidget.vue';

interface AuthUser {
    id: number;
    name: string;
    rol?: string;
}

interface SharedPageProps extends Record<string, unknown> {
    carrito?: {
        cantidad_items?: number;
    };
    flash?: {
        success?: string | null;
        error?: string | null;
    };
    auth?: {
        user?: AuthUser | null;
    };
}

const page = usePage<SharedPageProps>();

const carritoCantidad = computed(() =>
    Number(page.props.carrito?.cantidad_items ?? 0),
);

const authUser = computed<AuthUser | null>(() => page.props.auth?.user ?? null);

const flashSuccess = computed(() => page.props.flash?.success ?? null);
const flashError = computed(() => page.props.flash?.error ?? null);

onMounted(() => {
    if (flashSuccess.value) {
        Swal.fire({
            icon: 'success',
            title: '¡Listo!',
            text: String(flashSuccess.value),
            confirmButtonColor: '#062A5E',
            background: '#ffffff',
            color: '#0f172a',
            customClass: {
                popup: 'rounded-[24px] shadow-xl',
                confirmButton: 'rounded-full px-5 py-2.5 font-semibold',
            },
        });

        return;
    }

    if (flashError.value) {
        Swal.fire({
            icon: 'error',
            title: 'Ups, hubo un problema',
            text: String(flashError.value),
            confirmButtonColor: '#30BEEF',
            background: '#ffffff',
            color: '#0f172a',
            customClass: {
                popup: 'rounded-[24px] shadow-xl',
                confirmButton: 'rounded-full px-5 py-2.5 font-semibold',
            },
        });
    }
});
</script>

<template>
    <div
        class="min-h-screen overflow-x-hidden bg-white text-slate-950 selection:bg-[#30BEEF]/25 selection:text-[#062A5E]"
    >
        <Head title="Punto Polar" />

        <WhaticketWidget />

        <StoreNavbar
            :carrito-cantidad="carritoCantidad"
            :auth-user="authUser"
        />

        <main class="relative w-full">
            <slot />
        </main>

        <StoreFooter />
    </div>

    <a
    href="https://wa.me/527771148125?text=Hola%2C%20me%20gustar%C3%ADa%20hacer%20una%20consulta%20sobre%20Punto%20Polar."
    target="_blank"
    rel="noopener noreferrer"
    aria-label="Enviar mensaje por WhatsApp"
    class="fixed bottom-5 right-5 z-50 flex h-16 w-16 items-center justify-center rounded-full bg-[#25D366] text-white shadow-[0_18px_45px_rgba(37,211,102,0.35)] transition-all duration-300 hover:-translate-y-1 hover:scale-105"
>
    <svg
        viewBox="0 0 32 32"
        class="h-8 w-8 fill-current"
        aria-hidden="true"
    >
        <path
            d="M16.04 3C9.39 3 4 8.27 4 14.78c0 2.5.82 4.82 2.22 6.73L4.77 29l7.71-2.02a12.35 12.35 0 0 0 3.56.52C22.69 27.5 28 22.23 28 15.72S22.69 3 16.04 3Zm0 22.34c-1.12 0-2.22-.18-3.26-.55l-.47-.17-4.58 1.2.87-4.43-.29-.45a10.1 10.1 0 0 1-1.62-5.48c0-5.3 4.39-9.62 9.8-9.62s9.8 4.32 9.8 9.62-4.39 9.88-9.8 9.88Zm5.36-7.35c-.29-.15-1.73-.84-2-.94-.27-.1-.47-.15-.67.15-.2.29-.77.94-.95 1.13-.17.2-.35.22-.64.07-.29-.15-1.22-.44-2.32-1.4-.86-.75-1.44-1.68-1.61-1.97-.17-.29-.02-.45.13-.59.13-.13.29-.35.44-.52.15-.17.2-.29.29-.49.1-.2.05-.37-.02-.52-.07-.15-.67-1.59-.92-2.18-.24-.57-.49-.49-.67-.5h-.57c-.2 0-.52.07-.79.37-.27.29-1.04 1-1.04 2.44 0 1.44 1.07 2.83 1.22 3.03.15.2 2.1 3.15 5.1 4.42.71.3 1.27.48 1.7.61.71.22 1.36.19 1.87.12.57-.08 1.73-.7 1.98-1.37.24-.67.24-1.24.17-1.37-.07-.12-.27-.2-.56-.35Z"
        />
    </svg>
</a>
</template>