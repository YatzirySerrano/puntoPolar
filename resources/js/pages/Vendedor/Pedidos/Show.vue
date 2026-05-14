<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useCurrency } from '@/composables/useCurrency';

interface HistorialRow {
    id: number;
    estatus: string;
    comentario?: string | null;
    created_at?: string | null;
    user?: { id: number; name: string } | null;
}

interface PagoRow {
    id: number;
    estatus: string;
    monto: number;
    moneda?: string | null;
    referencia_externa?: string | null;
    autorizacion?: string | null;
    pagado_en?: string | null;
    metodo_pago?: { nombre: string; clave: string } | null;
}

interface ItemRow {
    id: number;
    producto_nombre?: string | null;
    producto_slug?: string | null;
    sku?: string | null;
    nombre: string;
    cantidad: number;
    precio_unitario: number;
    subtotal: number;
}

interface DireccionRow {
    alias?: string | null;
    nombre_receptor?: string | null;
    telefono?: string | null;
    calle?: string | null;
    numero_exterior?: string | null;
    numero_interior?: string | null;
    colonia?: string | null;
    municipio?: string | null;
    estado?: string | null;
    pais?: string | null;
    codigo_postal?: string | null;
    referencias?: string | null;
}

interface PedidoDetail {
    id: number;
    folio: string;
    estatus: string;
    estatus_siguientes?: string[];
    tipo_entrega?: 'recoleccion' | 'entrega_local' | null;
    codigo_recoleccion?: string | null;
    moneda?: string | null;
    subtotal: number;
    descuento: number;
    envio: number;
    impuesto: number;
    total: number;
    nombre_cliente?: string | null;
    correo_cliente?: string | null;
    telefono_cliente?: string | null;
    notas_cliente?: string | null;
    comentario_interno?: string | null;
    preparando_en?: string | null;
    listo_para_recoger_en?: string | null;
    fecha_entrega_programada?: string | null;
    salio_a_entrega_en?: string | null;
    zona_entrega?: string | null;
    instrucciones_entrega?: string | null;
    entregado_en?: string | null;
    pagado_en?: string | null;
    created_at?: string | null;
    direccion?: DireccionRow | null;
    items: ItemRow[];
    pagos: PagoRow[];
    historial: HistorialRow[];
}

const props = defineProps<{
    pedido: PedidoDetail;
    estatusDisponibles: string[];
}>();

const { formatCurrency } = useCurrency();

const esRecoleccion = computed(() => {
    return (props.pedido.tipo_entrega ?? 'recoleccion') === 'recoleccion';
});

const metodoEntregaLabel = computed(() => {
    return esRecoleccion.value ? 'Recolección en Punto Polar' : 'Entrega local';
});

const estatusSiguientes = computed(() => {
    return props.pedido.estatus_siguientes ?? [];
});

const form = useForm({
    estatus: props.pedido.estatus,
    comentario: '',
    comentario_interno: props.pedido.comentario_interno || '',
    fecha_entrega_programada: props.pedido.fecha_entrega_programada || '',
    zona_entrega: props.pedido.zona_entrega || '',
    instrucciones_entrega: props.pedido.instrucciones_entrega || '',
});

function estatusLabel(estatus: string) {
    const labels: Record<string, string> = {
        pendiente: 'Pendiente',
        pagado: 'Pagado',
        preparando: 'Preparando',
        listo_para_recoger: 'Listo para recoger',
        salio_a_entrega: 'Salió a entrega',
        entregado: 'Entregado',
        cancelado: 'Cancelado',
        reembolsado: 'Reembolsado',
        aprobado: 'Aprobado',
        rechazado: 'Rechazado',
        error: 'Error',
        procesando: 'Procesando',
    };

    return labels[estatus] ?? estatus;
}

function estatusClasses(estatus: string) {
    const classes: Record<string, string> = {
        pendiente: 'bg-amber-100 text-amber-700',
        pagado: 'bg-emerald-100 text-emerald-700',
        preparando: 'bg-sky-100 text-sky-700',
        listo_para_recoger: 'bg-cyan-100 text-cyan-700',
        salio_a_entrega: 'bg-indigo-100 text-indigo-700',
        entregado: 'bg-lime-100 text-lime-700',
        cancelado: 'bg-red-100 text-red-700',
        reembolsado: 'bg-purple-100 text-purple-700',
        aprobado: 'bg-emerald-100 text-emerald-700',
        rechazado: 'bg-red-100 text-red-700',
        error: 'bg-red-100 text-red-700',
        procesando: 'bg-blue-100 text-blue-700',
    };

    return classes[estatus] ?? 'bg-neutral-100 text-neutral-700';
}

function formatDate(value?: string | null) {
    if (!value) return 'Sin registro';

    return new Date(value).toLocaleString('es-MX', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function direccionCompleta(direccion?: DireccionRow | null) {
    if (!direccion) return 'Sin dirección registrada';

    return [
        direccion.calle,
        direccion.numero_exterior,
        direccion.numero_interior ? `Int. ${direccion.numero_interior}` : null,
        direccion.colonia,
        direccion.municipio,
        direccion.estado,
        direccion.pais,
        direccion.codigo_postal ? `CP ${direccion.codigo_postal}` : null,
    ]
        .filter(Boolean)
        .join(', ');
}

function cambiarEstatus(estatus: string) {
    form.estatus = estatus;
    submitStatus();
}

function submitStatus() {
    form.patch(`/vendedor/pedidos/${props.pedido.id}/estatus`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Operación ${pedido.folio}`" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <p class="text-sm text-neutral-500">Pedido operativo</p>
                <div class="mt-1 flex flex-wrap items-center gap-3">
                    <h1 class="text-3xl font-black">{{ pedido.folio }}</h1>
                    <span
                        class="inline-flex items-center rounded-full px-4 py-1.5 text-xs font-black uppercase tracking-[0.12em]"
                        :class="estatusClasses(pedido.estatus)"
                    >
                        {{ estatusLabel(pedido.estatus) }}
                    </span>
                </div>
            </div>

            <Link
                href="/vendedor/pedidos"
                class="inline-flex items-center justify-center rounded-full border border-neutral-200 px-5 py-2.5 text-sm font-bold transition hover:bg-neutral-50"
            >
                Volver
            </Link>
        </div>

        <section class="grid gap-4 md:grid-cols-4">
            <article class="rounded-2xl border bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-neutral-500">Subtotal</p>
                <p class="mt-2 text-2xl font-black">
                    {{ formatCurrency(pedido.subtotal) }}
                </p>
            </article>

            <article class="rounded-2xl border bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-neutral-500">Entrega</p>
                <p class="mt-2 text-2xl font-black">
                    {{ formatCurrency(pedido.envio) }}
                </p>
            </article>

            <article class="rounded-2xl border bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-neutral-500">Total</p>
                <p class="mt-2 text-2xl font-black">
                    {{ formatCurrency(pedido.total) }}
                </p>
            </article>

            <article class="rounded-2xl border bg-white p-4 shadow-sm">
                <p class="text-xs uppercase text-neutral-500">Método</p>
                <p class="mt-2 text-2xl font-black">
                    {{ metodoEntregaLabel }}
                </p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <div class="space-y-6">
                <article class="rounded-2xl border bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black">Cliente</h2>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div>
                            <p class="text-xs uppercase text-neutral-500">
                                Nombre
                            </p>
                            <p class="font-semibold">
                                {{ pedido.nombre_cliente || 'N/A' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs uppercase text-neutral-500">
                                Correo
                            </p>
                            <p class="font-semibold">
                                {{ pedido.correo_cliente || 'N/A' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs uppercase text-neutral-500">
                                Teléfono
                            </p>
                            <p class="font-semibold">
                                {{ pedido.telefono_cliente || 'N/A' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs uppercase text-neutral-500">
                                Pagado
                            </p>
                            <p class="font-semibold">
                                {{ formatDate(pedido.pagado_en) }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="pedido.notas_cliente"
                        class="mt-4 rounded-2xl bg-neutral-50 p-4"
                    >
                        <p class="text-xs uppercase text-neutral-500">
                            Notas del cliente
                        </p>
                        <p class="mt-2 text-sm text-neutral-700">
                            {{ pedido.notas_cliente }}
                        </p>
                    </div>
                </article>

                <article class="rounded-2xl border bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black">Método de entrega</h2>

                    <div
                        class="mt-4 rounded-2xl border border-[#30beef]/20 bg-[#30beef]/5 p-5 text-sm leading-7"
                    >
                        <p class="font-black text-neutral-900">
                            {{ metodoEntregaLabel }}
                        </p>

                        <template v-if="esRecoleccion">
                            <p class="mt-2 text-neutral-600">
                                El cliente recogerá el pedido en Punto Polar.
                            </p>

                            <p
                                v-if="pedido.codigo_recoleccion"
                                class="mt-3 rounded-xl bg-white px-4 py-3 font-black text-[#062A5E]"
                            >
                                Código de recolección:
                                {{ pedido.codigo_recoleccion }}
                            </p>

                            <p
                                v-if="pedido.listo_para_recoger_en"
                                class="mt-2 text-neutral-600"
                            >
                                Listo desde:
                                {{ formatDate(pedido.listo_para_recoger_en) }}
                            </p>
                        </template>

                        <template v-else>
                            <p class="mt-2 text-neutral-600">
                                Pedido con entrega local propia.
                            </p>

                            <p
                                v-if="pedido.fecha_entrega_programada"
                                class="mt-2"
                            >
                                Fecha programada:
                                {{
                                    formatDate(
                                        pedido.fecha_entrega_programada,
                                    )
                                }}
                            </p>

                            <p v-if="pedido.zona_entrega" class="mt-2">
                                Zona: {{ pedido.zona_entrega }}
                            </p>

                            <p
                                v-if="pedido.salio_a_entrega_en"
                                class="mt-2"
                            >
                                Salió a entrega:
                                {{ formatDate(pedido.salio_a_entrega_en) }}
                            </p>

                            <p
                                v-if="pedido.instrucciones_entrega"
                                class="mt-2"
                            >
                                Indicaciones:
                                {{ pedido.instrucciones_entrega }}
                            </p>
                        </template>
                    </div>
                </article>

                <article
                    v-if="!esRecoleccion"
                    class="rounded-2xl border bg-white p-5 shadow-sm"
                >
                    <h2 class="text-lg font-black">Dirección de entrega</h2>

                    <div
                        v-if="pedido.direccion"
                        class="mt-4 grid gap-3 sm:grid-cols-2"
                    >
                        <div>
                            <p class="text-xs uppercase text-neutral-500">
                                Alias
                            </p>
                            <p class="font-semibold">
                                {{ pedido.direccion.alias || 'N/A' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs uppercase text-neutral-500">
                                Receptor
                            </p>
                            <p class="font-semibold">
                                {{ pedido.direccion.nombre_receptor || 'N/A' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs uppercase text-neutral-500">
                                Teléfono
                            </p>
                            <p class="font-semibold">
                                {{ pedido.direccion.telefono || 'N/A' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs uppercase text-neutral-500">
                                CP
                            </p>
                            <p class="font-semibold">
                                {{ pedido.direccion.codigo_postal || 'N/A' }}
                            </p>
                        </div>

                        <div class="sm:col-span-2">
                            <p class="text-xs uppercase text-neutral-500">
                                Dirección completa
                            </p>
                            <p class="font-semibold">
                                {{ direccionCompleta(pedido.direccion) }}
                            </p>
                        </div>

                        <div
                            v-if="pedido.direccion.referencias"
                            class="sm:col-span-2"
                        >
                            <p class="text-xs uppercase text-neutral-500">
                                Referencias
                            </p>
                            <p class="font-semibold">
                                {{ pedido.direccion.referencias }}
                            </p>
                        </div>
                    </div>

                    <p v-else class="mt-4 text-sm text-neutral-500">
                        Este pedido no tiene dirección asociada.
                    </p>
                </article>

                <article class="rounded-2xl border bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black">Productos</h2>

                    <div class="mt-4 space-y-3">
                        <div
                            v-for="item in pedido.items"
                            :key="item.id"
                            class="rounded-2xl border border-neutral-200 p-4"
                        >
                            <div
                                class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <p class="font-black">{{ item.nombre }}</p>
                                    <p class="text-sm text-neutral-500">
                                        SKU: {{ item.sku || 'N/A' }}
                                    </p>
                                </div>

                                <div class="text-sm sm:text-right">
                                    <p>
                                        Cantidad:
                                        <strong>{{ item.cantidad }}</strong>
                                    </p>
                                    <p>
                                        Unitario:
                                        <strong>{{
                                            formatCurrency(item.precio_unitario)
                                        }}</strong>
                                    </p>
                                    <p>
                                        Subtotal:
                                        <strong>{{
                                            formatCurrency(item.subtotal)
                                        }}</strong>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <div class="space-y-6">
                <article class="rounded-2xl border bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black">Seguimiento operativo</h2>

                    <div v-if="estatusSiguientes.length" class="mt-4 space-y-3">
                        <button
                            v-for="estatus in estatusSiguientes"
                            :key="estatus"
                            type="button"
                            class="w-full rounded-full bg-[#062A5E] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#30BEEF] disabled:opacity-60"
                            :disabled="form.processing"
                            @click="cambiarEstatus(estatus)"
                        >
                            Marcar como {{ estatusLabel(estatus) }}
                        </button>
                    </div>

                    <p
                        v-else
                        class="mt-4 rounded-2xl border border-neutral-200 bg-neutral-50 p-4 text-sm font-semibold text-neutral-600"
                    >
                        Este pedido ya no tiene acciones operativas pendientes.
                    </p>

                    <form class="mt-6 space-y-4" @submit.prevent="submitStatus">
                        <div v-if="!esRecoleccion">
                            <label
                                class="mb-2 block text-sm font-semibold text-neutral-700"
                            >
                                Fecha de entrega programada
                            </label>
                            <input
                                v-model="form.fecha_entrega_programada"
                                type="datetime-local"
                                class="w-full rounded-xl border px-3 py-2 text-sm"
                            />
                        </div>

                        <div v-if="!esRecoleccion">
                            <label
                                class="mb-2 block text-sm font-semibold text-neutral-700"
                            >
                                Zona de entrega
                            </label>
                            <input
                                v-model="form.zona_entrega"
                                type="text"
                                class="w-full rounded-xl border px-3 py-2 text-sm"
                                placeholder="Ej. Jiutepec, Centro, Tejalpa..."
                            />
                        </div>

                        <div v-if="!esRecoleccion">
                            <label
                                class="mb-2 block text-sm font-semibold text-neutral-700"
                            >
                                Instrucciones de entrega
                            </label>
                            <textarea
                                v-model="form.instrucciones_entrega"
                                rows="3"
                                class="w-full rounded-xl border px-3 py-2 text-sm"
                                placeholder="Indicaciones para el repartidor"
                            />
                        </div>

                        <div>
                            <label
                                class="mb-2 block text-sm font-semibold text-neutral-700"
                            >
                                Comentario del movimiento
                            </label>
                            <textarea
                                v-model="form.comentario"
                                rows="3"
                                class="w-full rounded-xl border px-3 py-2 text-sm"
                                placeholder="Comentario opcional para historial"
                            />
                        </div>

                        <div>
                            <label
                                class="mb-2 block text-sm font-semibold text-neutral-700"
                            >
                                Nota interna
                            </label>
                            <textarea
                                v-model="form.comentario_interno"
                                rows="4"
                                class="w-full rounded-xl border px-3 py-2 text-sm"
                                placeholder="Notas internas del pedido"
                            />
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full rounded-full bg-[linear-gradient(135deg,#30BEEF_0%,#062A5E_100%)] px-4 py-2.5 text-sm font-bold text-white transition hover:brightness-110 disabled:opacity-60"
                        >
                            {{ form.processing ? 'Guardando...' : 'Guardar datos operativos' }}
                        </button>
                    </form>
                </article>

                <article class="rounded-2xl border bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black">Fechas</h2>
                    <div class="mt-4 space-y-2 text-sm">
                        <p><strong>Creado:</strong> {{ formatDate(pedido.created_at) }}</p>
                        <p><strong>Pagado:</strong> {{ formatDate(pedido.pagado_en) }}</p>
                        <p><strong>Preparando:</strong> {{ formatDate(pedido.preparando_en) }}</p>
                        <p v-if="esRecoleccion">
                            <strong>Listo para recoger:</strong>
                            {{ formatDate(pedido.listo_para_recoger_en) }}
                        </p>
                        <p v-else>
                            <strong>Salió a entrega:</strong>
                            {{ formatDate(pedido.salio_a_entrega_en) }}
                        </p>
                        <p><strong>Entregado:</strong> {{ formatDate(pedido.entregado_en) }}</p>
                    </div>
                </article>

                <article class="rounded-2xl border bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black">Historial</h2>

                    <div v-if="pedido.historial.length" class="mt-4 space-y-3">
                        <div
                            v-for="row in pedido.historial"
                            :key="row.id"
                            class="rounded-2xl border border-neutral-200 p-4"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-black uppercase">
                                    {{ estatusLabel(row.estatus) }}
                                </p>
                                <p class="text-xs text-neutral-500">
                                    {{ formatDate(row.created_at) }}
                                </p>
                            </div>
                            <p class="mt-1 text-sm text-neutral-500">
                                {{ row.user?.name || 'Sistema' }}
                            </p>
                            <p
                                v-if="row.comentario"
                                class="mt-2 text-sm text-neutral-700"
                            >
                                {{ row.comentario }}
                            </p>
                        </div>
                    </div>

                    <p v-else class="mt-4 text-sm text-neutral-500">
                        Aún no hay historial de movimientos.
                    </p>
                </article>
            </div>
        </section>
    </div>
</template>