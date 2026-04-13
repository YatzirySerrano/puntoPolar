<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
defineProps<{
    categorias: {
        data: Array<{
            id: number;
            nombre: string;
            slug: string;
            activa: boolean;
        }>;
    };
}>();
</script>
<template>
    <Head title="Admin · Categorías" />
    <div class="space-y-4 p-4 sm:p-6 lg:p-8">
        <h1 class="text-2xl font-black">Categorías</h1>
        <div class="overflow-hidden rounded-2xl border bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-neutral-50 text-left">
                    <tr>
                        <th class="px-4 py-2">Nombre</th>
                        <th class="px-4 py-2">Slug</th>
                        <th class="px-4 py-2">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in categorias.data"
                        :key="row.id"
                        class="border-t"
                    >
                        <td class="px-4 py-2">{{ row.nombre }}</td>
                        <td class="px-4 py-2">{{ row.slug }}</td>
                        <td class="px-4 py-2">
                            {{ row.activa ? 'Activa' : 'Inactiva' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
