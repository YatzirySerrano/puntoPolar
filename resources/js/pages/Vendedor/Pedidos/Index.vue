<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useCurrency } from '@/composables/useCurrency';

interface Pedido {
    id: number;
    folio: string;
    estatus: string;
    total: number | string;
    nombre_cliente: string;
    correo_cliente?: string | null;
    items_count?: number;
}

const props = defineProps<{
    pedidos: { data: Pedido[] };
    estatusDisponibles: string[];
}>();

const { formatCurrency } = useCurrency();
const form = useForm({ estatus: '', comentario: '' });

const actualizarEstatus = (pedidoId: number, estatus: string) => {
    form.estatus = estatus;
    form.comentario = '';
    form.patch(`/vendedor/pedidos/${pedidoId}/estatus`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Vendedor · Pedidos" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <header
            class="rounded-3xl border border-[var(--brand-gray)]/60 bg-gradient-to-r from-white to-[var(--brand-soft)] p-4 shadow-sm sm:p-6"
        >
            <h1 class="text-2xl font-black">Pedidos operativos</h1>
            <p class="text-sm text-neutral-500">
                Módulo para seguimiento operativo de pedidos pagados.
            </p>
        </header>

        <div class="grid gap-4 lg:grid-cols-2 2xl:grid-cols-3">
            <article
                v-for="pedido in props.pedidos.data"
                :key="pedido.id"
                class="rounded-2xl border bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-md"
            >
                <p class="text-xs text-neutral-500">{{ pedido.folio }}</p>
                <p class="font-black">{{ pedido.nombre_cliente }}</p>
                <p class="text-sm text-neutral-500">{{ pedido.correo_cliente || 'Sin correo' }}</p>
                <p class="mt-2 text-lg font-black">
                    {{ formatCurrency(pedido.total) }}
                </p>
                <p class="mt-1 text-sm text-neutral-500">
                    Items: {{ pedido.items_count ?? 0 }}
                </p>

                <select
                    class="mt-4 h-10 w-full rounded-xl border px-3 text-sm"
                    :value="pedido.estatus"
                    @change="
                        actualizarEstatus(
                            pedido.id,
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

                <Link
                    :href="`/vendedor/pedidos/${pedido.id}`"
                    class="mt-4 inline-flex w-full items-center justify-center rounded-full border border-neutral-200 px-4 py-2 text-sm font-bold transition hover:bg-neutral-50"
                >
                    Ver detalle
                </Link>
            </article>
        </div>
    </div>
</template>
