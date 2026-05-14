<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useCurrency } from '@/composables/useCurrency';

interface Pedido {
    id: number;
    folio: string;
    estatus: string;
    tipo_entrega?: 'recoleccion' | 'entrega_local' | null;
    codigo_recoleccion?: string | null;
    listo_para_recoger_en?: string | null;
    salio_a_entrega_en?: string | null;
    zona_entrega?: string | null;
    total: number | string;
    nombre_cliente: string;
    correo_cliente?: string | null;
    telefono_cliente?: string | null;
    items_count?: number;
    created_at?: string | null;
}

const props = defineProps<{
    pedidos: { data: Pedido[] };
    estatusDisponibles: string[];
}>();

const { formatCurrency } = useCurrency();
const form = useForm({ estatus: '', comentario: '' });

const estatusLabel = (estatus: string) => {
    const labels: Record<string, string> = {
        pagado: 'Pagado',
        preparando: 'Preparando',
        listo_para_recoger: 'Listo para recoger',
        salio_a_entrega: 'Salió a entrega',
        entregado: 'Entregado',
    };

    return labels[estatus] ?? estatus;
};

const estatusClasses = (estatus: string) => {
    const classes: Record<string, string> = {
        pagado: 'bg-emerald-100 text-emerald-700',
        preparando: 'bg-sky-100 text-sky-700',
        listo_para_recoger: 'bg-cyan-100 text-cyan-700',
        salio_a_entrega: 'bg-indigo-100 text-indigo-700',
        entregado: 'bg-lime-100 text-lime-700',
    };

    return classes[estatus] ?? 'bg-neutral-100 text-neutral-700';
};

const metodoEntregaLabel = (pedido: Pedido) => {
    return (pedido.tipo_entrega ?? 'recoleccion') === 'recoleccion'
        ? 'Recolección'
        : 'Entrega local';
};

const entregaDetalle = (pedido: Pedido) => {
    if ((pedido.tipo_entrega ?? 'recoleccion') === 'recoleccion') {
        return pedido.codigo_recoleccion || 'Código pendiente';
    }

    return pedido.zona_entrega || 'Zona sin registrar';
};

const formatDate = (value?: string | null) => {
    if (!value) return 'Sin fecha';

    return new Date(value).toLocaleString('es-MX', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

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
            class="rounded-3xl border border-[#30BEEF]/20 bg-gradient-to-r from-white to-[#30BEEF]/10 p-4 shadow-sm sm:p-6"
        >
            <h1 class="text-2xl font-black">Pedidos operativos</h1>
            <p class="text-sm text-neutral-500">
                Seguimiento de pedidos pagados para recolección y entrega local.
            </p>
        </header>

        <div
            v-if="pedidos.data.length"
            class="grid gap-4 lg:grid-cols-2 2xl:grid-cols-3"
        >
            <article
                v-for="pedido in props.pedidos.data"
                :key="pedido.id"
                class="rounded-2xl border bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-md"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs text-neutral-500">
                            {{ pedido.folio }}
                        </p>
                        <p class="font-black">{{ pedido.nombre_cliente }}</p>
                    </div>

                    <span
                        class="rounded-full px-3 py-1 text-xs font-black uppercase tracking-[0.12em]"
                        :class="estatusClasses(pedido.estatus)"
                    >
                        {{ estatusLabel(pedido.estatus) }}
                    </span>
                </div>

                <p class="mt-2 text-sm text-neutral-500">
                    {{ pedido.correo_cliente || 'Sin correo' }}
                </p>

                <p v-if="pedido.telefono_cliente" class="text-sm text-neutral-500">
                    {{ pedido.telefono_cliente }}
                </p>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl border border-neutral-200 bg-neutral-50 p-3">
                        <p class="text-xs font-black uppercase text-neutral-400">
                            Total
                        </p>
                        <p class="mt-1 text-lg font-black">
                            {{ formatCurrency(pedido.total) }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-neutral-200 bg-neutral-50 p-3">
                        <p class="text-xs font-black uppercase text-neutral-400">
                            Productos
                        </p>
                        <p class="mt-1 text-lg font-black">
                            {{ pedido.items_count ?? 0 }}
                        </p>
                    </div>
                </div>

                <div
                    class="mt-4 rounded-2xl border border-[#30BEEF]/20 bg-[#30BEEF]/5 p-4"
                >
                    <p class="text-xs font-black uppercase text-[#062A5E]">
                        {{ metodoEntregaLabel(pedido) }}
                    </p>
                    <p class="mt-1 text-sm font-semibold text-neutral-800">
                        {{ entregaDetalle(pedido) }}
                    </p>

                    <p
                        v-if="pedido.listo_para_recoger_en"
                        class="mt-2 text-xs text-neutral-500"
                    >
                        Listo:
                        {{ formatDate(pedido.listo_para_recoger_en) }}
                    </p>

                    <p
                        v-if="pedido.salio_a_entrega_en"
                        class="mt-2 text-xs text-neutral-500"
                    >
                        Salió:
                        {{ formatDate(pedido.salio_a_entrega_en) }}
                    </p>
                </div>

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
                        {{ estatusLabel(estatus) }}
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

        <section
            v-else
            class="rounded-3xl border border-dashed border-neutral-300 bg-white p-10 text-center"
        >
            <p class="text-lg font-black">No hay pedidos operativos</p>
            <p class="mt-2 text-sm text-neutral-500">
                Cuando existan pedidos pagados aparecerán en esta sección.
            </p>
        </section>
    </div>
</template>