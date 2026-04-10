<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
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

const mostrarFlash = () => {
    if (flashSuccess.value) {
        Swal.fire({
            icon: 'success',
            title: 'Éxito',
            text: String(flashSuccess.value),
            confirmButtonColor: '#7dd03c',
        });
    }

    if (flashError.value) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
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

        <StoreNavbar :carrito-cantidad="carritoCantidad" />

        <main>
            <slot />
        </main>

        <StoreFooter />
    </div>
</template>
