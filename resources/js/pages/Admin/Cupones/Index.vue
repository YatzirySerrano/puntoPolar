<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
defineProps<{
    cupones: {
        data: Array<{
            id: number;
            codigo: string;
            nombre: string;
            tipo: string;
            valor: number | string;
            activo: boolean;
        }>;
    };
}>();
</script>
<template>
    <Head title="Admin · Cupones" />
    <div class="space-y-4 p-4 sm:p-6 lg:p-8">
        <h1 class="text-2xl font-black">Cupones</h1>
        <div class="overflow-hidden rounded-2xl border bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-3 py-2">Código</th>
                        <th class="px-3 py-2">Nombre</th>
                        <th class="px-3 py-2">Tipo</th>
                        <th class="px-3 py-2">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in cupones.data"
                        :key="row.id"
                        class="border-t"
                    >
                        <td class="px-3 py-2">{{ row.codigo }}</td>
                        <td class="px-3 py-2">{{ row.nombre }}</td>
                        <td class="px-3 py-2">{{ row.tipo }}</td>
                        <td class="px-3 py-2">{{ row.valor }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
