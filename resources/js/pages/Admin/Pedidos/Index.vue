<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
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

defineProps<{
    pedidos: { data: Pedido[] };
    estatusDisponibles: string[];
    filters: { estatus?: string };
}>();

const { formatCurrency } = useCurrency();
const form = useForm({ estatus: '' });

const actualizarEstatus = (pedido: Pedido, estatus: string) => {
    form.estatus = estatus;
    form.patch(`/admin/pedidos/${pedido.id}/estatus`, { preserveScroll: true });
};
</script>

<template>
    <Head title="Admin · Pedidos" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <header class="rounded-2xl border bg-white p-4 shadow-sm sm:p-6">
            <h1 class="text-2xl font-black">Gestión de pedidos</h1>
            <div class="mt-3 flex flex-wrap gap-2">
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
                class="rounded-2xl border bg-white p-5 shadow-sm"
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
