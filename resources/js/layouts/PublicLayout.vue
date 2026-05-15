<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { computed, onMounted } from 'vue';
import StoreFooter from '@/components/public/StoreFooter.vue';
import StoreNavbar from '@/components/public/StoreNavbar.vue';
import WhaticketWidget from '@/components/integrations/WhaticketWidget.vue';

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
</template>