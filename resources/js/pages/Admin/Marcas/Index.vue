<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
defineProps<{
    marcas: {
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
    <Head title="Admin · Marcas" />
    <div class="space-y-4 p-4 sm:p-6 lg:p-8">
        <h1 class="text-2xl font-black">Marcas</h1>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article
                v-for="row in marcas.data"
                :key="row.id"
                class="rounded-2xl border bg-white p-4"
            >
                <p class="font-black">{{ row.nombre }}</p>
                <p class="text-xs text-neutral-500">{{ row.slug }}</p>
                <p class="mt-2 text-xs">
                    {{ row.activa ? 'Activa' : 'Inactiva' }}
                </p>
            </article>
        </div>
    </div>
</template>
