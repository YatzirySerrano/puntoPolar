<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { computed, onMounted, watch } from 'vue';
import { useCurrency } from '@/composables/useCurrency';

interface Pedido {
    id: number;
    folio: string;
    estatus: string;
    total: number | string;
    created_at: string;
    nombre_cliente: string;
    correo_cliente: string;
    items: Array<{ id: number; cantidad: number }>;
}

const page = usePage();

const mostrarFlash = () => {
    const ok = page.props.flash?.success;
    const err = page.props.flash?.error;

    if (ok) {
        Swal.fire({
            icon: 'success',
            title: 'Éxito',
            text: String(ok),
            timer: 1800,
            showConfirmButton: false,
        });
    }

    if (err) {
        Swal.fire({ icon: 'error', title: 'Error', text: String(err) });
    }
};

onMounted(mostrarFlash);
watch(() => page.props.flash, mostrarFlash, { deep: true });

const props = defineProps<{
    pedidos: { data: Pedido[] };
    estatusDisponibles: string[];
    filters: { estatus?: string };
}>();

const { formatCurrency } = useCurrency();
const form = useForm({ estatus: '' });

const totalVentas = computed(() =>
    props.pedidos.data.reduce((acc, pedido) => acc + Number(pedido.total), 0),
);

const actualizarEstatus = async (pedido: Pedido, estatus: string) => {
    if (pedido.estatus === estatus) {
        return;
    }

    const result = await Swal.fire({
        title: '¿Actualizar estatus?',
        text: `${pedido.folio} → ${estatus}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Actualizar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#30beef',
    });

    if (!result.isConfirmed) {
        return;
    }

    form.estatus = estatus;
    form.patch(`/admin/pedidos/${pedido.id}/estatus`, { preserveScroll: true });
};
</script>

<template>
    <Head title="Admin · Pedidos" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <header
            class="rounded-3xl border border-[var(--brand-gray)]/60 bg-gradient-to-r from-white to-[var(--brand-soft)] p-5 shadow-sm sm:p-6"
        >
            <h1 class="text-2xl font-black sm:text-3xl">Gestión de pedidos</h1>
            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <article class="rounded-2xl bg-white p-4 shadow-sm">
                    <p class="text-xs text-neutral-500 uppercase">Pedidos</p>
                    <p class="text-2xl font-black">{{ pedidos.data.length }}</p>
                </article>
                <article class="rounded-2xl bg-white p-4 shadow-sm">
                    <p class="text-xs text-neutral-500 uppercase">Ventas</p>
                    <p class="text-2xl font-black">
                        {{ formatCurrency(totalVentas) }}
                    </p>
                </article>
                <article class="rounded-2xl bg-white p-4 shadow-sm">
                    <p class="text-xs text-neutral-500 uppercase">
                        Filtro activo
                    </p>
                    <p class="text-2xl font-black">
                        {{ filters.estatus || 'Todos' }}
                    </p>
                </article>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button
                    type="button"
                    class="rounded-full border px-4 py-1.5 text-xs font-bold uppercase"
                    :class="
                        !filters.estatus
                            ? 'bg-[var(--brand-blue)] text-white'
                            : 'bg-white'
                    "
                    @click="
                        router.get(
                            '/admin/pedidos',
                            {},
                            { preserveState: true, replace: true },
                        )
                    "
                >
                    Todos
                </button>
                <button
                    v-for="estatus in estatusDisponibles"
                    :key="estatus"
                    type="button"
                    class="rounded-full border px-4 py-1.5 text-xs font-bold uppercase"
                    :class="
                        filters.estatus === estatus
                            ? 'bg-[var(--brand-blue)] text-white'
                            : 'bg-white'
                    "
                    @click="
                        router.get(
                            '/admin/pedidos',
                            { estatus },
                            { preserveState: true, replace: true },
                        )
                    "
                >
                    {{ estatus }}
                </button>
            </div>
        </header>

        <div class="grid gap-4 lg:grid-cols-2 2xl:grid-cols-3">
            <article
                v-for="pedido in pedidos.data"
                :key="pedido.id"
                class="rounded-2xl border bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-md"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs text-neutral-500">
                            {{ pedido.folio }}
                        </p>
                        <p class="font-black">{{ pedido.nombre_cliente }}</p>
                        <p class="text-xs text-neutral-500">
                            {{ pedido.correo_cliente }}
                        </p>
                    </div>
                    <span
                        class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold uppercase"
                        >{{ pedido.estatus }}</span
                    >
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                    <p>Total:</p>
                    <p class="text-right font-black">
                        {{ formatCurrency(pedido.total) }}
                    </p>
                    <p>Items:</p>
                    <p class="text-right font-black">
                        {{ pedido.items.length }}
                    </p>
                </div>

                <select
                    class="mt-4 h-10 w-full rounded-xl border px-3 text-sm"
                    :value="pedido.estatus"
                    @change="
                        actualizarEstatus(
                            pedido,
                            ($event.target as HTMLSelectElement).value,
                        )
                    "
                >
                    <option
                        v-for="estatus in estatusDisponibles"
                        :key="estatus"
                        :value="estatus"
                    >
                        {{ estatus }}
                    </option>
                </select>
            </article>
        </div>
    </div>
</template>
