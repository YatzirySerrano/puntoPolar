<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { computed, onMounted, watch } from 'vue';
import StoreFooter from '@/components/public/StoreFooter.vue';
import StoreNavbar from '@/components/public/StoreNavbar.vue';

const page = usePage();

const carritoCantidad = computed(() =>
    Number(page.props.carrito?.cantidad_items ?? 0),
);
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const authUser = computed(() => page.props.auth?.user ?? null);

const mostrarFlash = () => {
    if (flashSuccess.value) {
        Swal.fire({
            icon: 'success',
            title: '¡Todo salió bien!',
            text: String(flashSuccess.value),
            confirmButtonColor: '#7dd03c',
        });
    }

    if (flashError.value) {
        Swal.fire({
            icon: 'error',
            title: 'Ups, hubo un problema',
            text: String(flashError.value),
            confirmButtonColor: '#30beef',
        });
    }
};

onMounted(mostrarFlash);
watch(() => page.props.flash, mostrarFlash, { deep: true });
</script>

<template>
    <div class="min-h-screen bg-white text-[var(--brand-text)]">
        <Head title="Mr. Lana" />

        <StoreNavbar
            :carrito-cantidad="carritoCantidad"
            :auth-user="authUser"
        />

        <main>
            <slot />
        </main>

        <Link
            href="/carrito"
            class="fixed right-5 bottom-5 z-50 flex items-center gap-2 rounded-full bg-gradient-to-r from-[var(--brand-green)] to-[var(--brand-blue)] px-4 py-3 text-sm font-black text-white shadow-xl transition hover:scale-[1.05]"
        >
            <span>Carrito</span>
            <span
                class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-white px-1 text-xs font-black text-[var(--brand-blue)]"
            >
                {{ carritoCantidad }}
            </span>
        </Link>

        <StoreFooter />
    </div>
</template>
