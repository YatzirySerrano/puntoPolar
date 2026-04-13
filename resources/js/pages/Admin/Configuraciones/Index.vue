<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
defineProps<{
    configuraciones: {
        data: Array<{ id: number; clave: string; valor: string }>;
    };
}>();
</script>
<template>
    <Head title="Admin · Configuraciones" />
    <div class="space-y-4 p-4 sm:p-6 lg:p-8">
        <h1 class="text-2xl font-black">Configuraciones</h1>
        <div class="overflow-hidden rounded-2xl border bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-3 py-2">Clave</th>
                        <th class="px-3 py-2">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in configuraciones.data"
                        :key="row.id"
                        class="border-t"
                    >
                        <td class="px-3 py-2">{{ row.clave }}</td>
                        <td class="px-3 py-2">{{ row.valor }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
