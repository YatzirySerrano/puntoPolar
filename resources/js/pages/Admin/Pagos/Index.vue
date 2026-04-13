<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
defineProps<{
    pagos: {
        data: Array<{
            id: number;
            estatus: string;
            monto: number | string;
            pedido?: { folio: string } | null;
            metodo_pago?: { nombre: string } | null;
        }>;
    };
}>();
</script>
<template>
    <Head title="Admin · Pagos" />
    <div class="space-y-4 p-4 sm:p-6 lg:p-8">
        <h1 class="text-2xl font-black">Pagos</h1>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="row in pagos.data"
                :key="row.id"
                class="rounded-2xl border bg-white p-4"
            >
                <p class="font-black">Pago #{{ row.id }}</p>
                <p class="text-xs">{{ row.estatus }}</p>
                <p class="mt-2">$ {{ row.monto }}</p>
                <p class="text-xs">Pedido: {{ row.pedido?.folio || 'N/A' }}</p>
            </article>
        </div>
    </div>
</template>
