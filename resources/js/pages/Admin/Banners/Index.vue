<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
defineProps<{
    banners: {
        data: Array<{
            id: number;
            titulo: string;
            imagen?: string | null;
            activo: boolean;
        }>;
    };
}>();
</script>
<template>
    <Head title="Admin · Banners" />
    <div class="space-y-4 p-4 sm:p-6 lg:p-8">
        <h1 class="text-2xl font-black">Banners</h1>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="row in banners.data"
                :key="row.id"
                class="rounded-2xl border bg-white p-4"
            >
                <p class="font-black">{{ row.titulo }}</p>
                <p class="text-xs">{{ row.activo ? 'Activo' : 'Inactivo' }}</p>
            </article>
        </div>
    </div>
</template>
