<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useCurrency } from '@/composables/useCurrency';

interface Pedido {
    id: number;
    folio: string;
    estatus: string;
    total: number | string;
    created_at: string;
    items: Array<{ id: number; nombre: string; cantidad: number }>;
}

defineProps<{
    pedidos: { data: Pedido[] };
}>();

const { formatCurrency } = useCurrency();

function formatDate(value?: string | null) {
    if (!value) return 'Sin fecha';
    return new Date(value).toLocaleDateString('es-MX');
}
</script>

<template>
    <Head title="Mis pedidos" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <header
            class="rounded-3xl border border-[var(--brand-gray)]/60 bg-gradient-to-r from-white to-[var(--brand-soft)] p-4 shadow-sm sm:p-6"
        >
            <h1 class="text-2xl font-black">Mis pedidos</h1>
            <p class="text-sm text-neutral-500">
                Historial y seguimiento de tus compras.
            </p>
        </header>

        <div v-if="pedidos.data.length" class="grid gap-4 lg:grid-cols-2">
            <article
                v-for="pedido in pedidos.data"
                :key="pedido.id"
                class="rounded-2xl border bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-md"
            >
                <div class="flex items-center justify-between gap-3">
                    <p class="font-black">{{ pedido.folio }}</p>
                    <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold uppercase">
                        {{ pedido.estatus }}
                    </span>
                </div>

                <p class="mt-2 text-sm text-neutral-500">
                    {{ formatDate(pedido.created_at) }}
                </p>

                <p class="mt-3 text-lg font-black">
                    {{ formatCurrency(pedido.total) }}
                </p>

                <div class="mt-4 rounded-2xl bg-neutral-50 p-4">
                    <p class="text-xs uppercase text-neutral-500">Productos</p>
                    <ul class="mt-2 space-y-1 text-sm text-neutral-700">
                        <li
                            v-for="item in pedido.items.slice(0, 3)"
                            :key="item.id"
                        >
                            {{ item.nombre }} × {{ item.cantidad }}
                        </li>
                    </ul>
                    <p
                        v-if="pedido.items.length > 3"
                        class="mt-2 text-xs text-neutral-500"
                    >
                        y {{ pedido.items.length - 3 }} más...
                    </p>
                </div>

                <Link
                    :href="`/mi-cuenta/pedidos/${pedido.id}`"
                    class="mt-4 inline-flex w-full items-center justify-center rounded-full bg-[var(--brand-blue)] px-4 py-2.5 text-sm font-bold text-white transition hover:brightness-90"
                >
                    Ver detalle
                </Link>
            </article>
        </div>

        <div
            v-else
            class="rounded-2xl border border-dashed bg-white px-6 py-16 text-center shadow-sm"
        >
            <h2 class="text-xl font-black">Todavía no tienes pedidos</h2>
            <p class="mt-2 text-sm text-neutral-500">
                Cuando realices compras, aquí aparecerá tu historial.
            </p>
        </div>
    </div>
</template>
