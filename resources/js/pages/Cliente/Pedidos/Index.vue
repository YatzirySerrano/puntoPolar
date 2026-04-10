<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
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
</script>

<template>
    <Head title="Mis pedidos" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <header class="rounded-2xl border bg-white p-4 shadow-sm sm:p-6">
            <h1 class="text-2xl font-black">Mis pedidos</h1>
            <p class="text-sm text-neutral-500">
                Historial de compras del cliente.
            </p>
        </header>

        <div class="grid gap-4 lg:grid-cols-2">
            <article
                v-for="pedido in pedidos.data"
                :key="pedido.id"
                class="rounded-2xl border bg-white p-5 shadow-sm"
            >
                <div class="flex items-center justify-between">
                    <p class="font-black">{{ pedido.folio }}</p>
                    <span
                        class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold uppercase"
                        >{{ pedido.estatus }}</span
                    >
                </div>
                <p class="mt-2 text-sm text-neutral-500">
                    {{
                        new Date(pedido.created_at).toLocaleDateString('es-MX')
                    }}
                </p>
                <p class="mt-2 text-lg font-black">
                    {{ formatCurrency(pedido.total) }}
                </p>
            </article>
        </div>
    </div>
</template>
