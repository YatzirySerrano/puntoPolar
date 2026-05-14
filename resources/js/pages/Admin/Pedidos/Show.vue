<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { computed, onMounted, ref, watch } from 'vue';
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
    producto_id?: number | null;
    producto_nombre?: string | null;
    producto_slug?: string | null;
    sku?: string | null;
    nombre: string;
    cantidad: number;
    precio_unitario: number;
    subtotal: number;
    imagen_url?: string | null;
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
    estatus_siguientes: string[];
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
    cancelado_en?: string | null;
    created_at?: string | null;
    user?: { id: number; name: string; email: string } | null;
    direccion?: DireccionRow | null;
    items: ItemRow[];
    pagos: PagoRow[];
    historial: HistorialRow[];
}

const props = defineProps<{
    pedido: PedidoDetail;
    estatusDisponibles: string[];
}>();

const page = usePage<{
    flash?: {
        success?: string;
        error?: string;
    };
}>();

const { formatCurrency } = useCurrency();

const editandoEntrega = ref(false);
const comentarioVisible = ref(false);

const esRecoleccion = computed(() => {
    return (props.pedido.tipo_entrega ?? 'recoleccion') === 'recoleccion';
});

const metodoEntregaLabel = computed(() => {
    return esRecoleccion.value ? 'Recolección en Punto Polar' : 'Entrega local';
});

const form = useForm({
    estatus: props.pedido.estatus,
    comentario: '',
    comentario_interno: props.pedido.comentario_interno || '',
    fecha_entrega_programada: props.pedido.fecha_entrega_programada || '',
    zona_entrega: props.pedido.zona_entrega || '',
    instrucciones_entrega: props.pedido.instrucciones_entrega || '',
});

const pagoMasReciente = computed(() => {
    if (!props.pedido.pagos.length) return null;

    return [...props.pedido.pagos].sort((a, b) => b.id - a.id)[0];
});

const puedeReintentarPago = computed(() => {
    const estatusPago = pagoMasReciente.value?.estatus;

    return (
        props.pedido.estatus === 'pendiente' ||
        estatusPago === 'pendiente' ||
        estatusPago === 'rechazado' ||
        estatusPago === 'error'
    );
});

const urlPago = computed(() => {
    return `/checkout/gracias/${props.pedido.id}`;
});

const totalPiezas = computed(() =>
    props.pedido.items.reduce(
        (acc, item) => acc + Number(item.cantidad || 0),
        0,
    ),
);

const timelineSteps = computed(() => {
    if (esRecoleccion.value) {
        return [
            'pendiente',
            'pagado',
            'preparando',
            'listo_para_recoger',
            'entregado',
        ];
    }

    return [
        'pendiente',
        'pagado',
        'preparando',
        'salio_a_entrega',
        'entregado',
    ];
});

const progresoPedido = computed(() => {
    if (props.pedido.estatus === 'cancelado') return 0;
    if (props.pedido.estatus === 'reembolsado') return 100;

    const index = timelineSteps.value.indexOf(props.pedido.estatus);

    if (index < 0) return 0;

    return Math.round((index / (timelineSteps.value.length - 1)) * 100);
});

const estatusLabel = (estatus: string) => {
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
};

const estatusClasses = (estatus: string) => {
    const classes: Record<string, string> = {
        pendiente: 'border-amber-200 bg-amber-50 text-amber-700',
        pagado: 'border-emerald-200 bg-emerald-50 text-emerald-700',
        preparando: 'border-sky-200 bg-sky-50 text-sky-700',
        listo_para_recoger: 'border-cyan-200 bg-cyan-50 text-cyan-700',
        salio_a_entrega: 'border-indigo-200 bg-indigo-50 text-indigo-700',
        entregado: 'border-lime-200 bg-lime-50 text-lime-700',
        cancelado: 'border-red-200 bg-red-50 text-red-700',
        reembolsado: 'border-purple-200 bg-purple-50 text-purple-700',
        aprobado: 'border-emerald-200 bg-emerald-50 text-emerald-700',
        rechazado: 'border-red-200 bg-red-50 text-red-700',
        error: 'border-red-200 bg-red-50 text-red-700',
        procesando: 'border-blue-200 bg-blue-50 text-blue-700',
    };

    return (
        classes[estatus] ?? 'border-neutral-200 bg-neutral-50 text-neutral-600'
    );
};

const formatDate = (value?: string | null) => {
    if (!value) return 'Sin registro';

    return new Date(value).toLocaleString('es-MX', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const direccionCompleta = (direccion?: DireccionRow | null) => {
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
};

const inicialProducto = (nombre: string) => {
    return nombre?.charAt(0)?.toUpperCase() || 'P';
};

const copiarLigaPago = async () => {
    const urlCompleta = `${window.location.origin}${urlPago.value}`;

    await navigator.clipboard.writeText(urlCompleta);

    Swal.fire({
        icon: 'success',
        title: 'Liga copiada',
        text: 'Ya puedes compartirla con el cliente para que vuelva a intentar el pago.',
        timer: 1800,
        showConfirmButton: false,
        confirmButtonColor: '#111827',
    });
};

const prepararCambioEstado = (estatus: string) => {
    form.estatus = estatus;
    form.comentario = '';
    form.comentario_interno =
        props.pedido.comentario_interno || form.comentario_interno;
    form.fecha_entrega_programada =
        props.pedido.fecha_entrega_programada || form.fecha_entrega_programada;
    form.zona_entrega = props.pedido.zona_entrega || form.zona_entrega;
    form.instrucciones_entrega =
        props.pedido.instrucciones_entrega || form.instrucciones_entrega;

    comentarioVisible.value = true;
};

const guardarCambioEstado = () => {
    form.patch(`/admin/pedidos/${props.pedido.id}/estatus`, {
        preserveScroll: true,
        onSuccess: () => {
            comentarioVisible.value = false;
            editandoEntrega.value = false;
        },
    });
};

const guardarDatosEntrega = () => {
    form.estatus = props.pedido.estatus;

    form.patch(`/admin/pedidos/${props.pedido.id}/estatus`, {
        preserveScroll: true,
        onSuccess: () => {
            editandoEntrega.value = false;
        },
    });
};

const mostrarFlash = () => {
    const ok = page.props.flash?.success;
    const err = page.props.flash?.error;

    if (ok) {
        Swal.fire({
            icon: 'success',
            title: 'Listo',
            text: String(ok),
            timer: 1800,
            showConfirmButton: false,
            confirmButtonColor: '#111827',
        });
    }

    if (err) {
        Swal.fire({
            icon: 'error',
            title: 'No se pudo completar',
            text: String(err),
            confirmButtonColor: '#111827',
        });
    }
};

onMounted(mostrarFlash);
watch(() => page.props.flash, mostrarFlash, { deep: true });
</script>

<template>
    <Head :title="`Pedido ${pedido.folio}`" />

    <div class="min-h-screen bg-[#f4f6f8] px-4 py-5 sm:px-5 lg:px-6">
        <div class="w-full space-y-5">
            <header
                class="rounded-[28px] border border-neutral-200 bg-white p-5 shadow-sm sm:p-6"
            >
                <div
                    class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between"
                >
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p
                                class="text-3xl font-black tracking-tight text-neutral-950"
                            >
                                {{ pedido.folio }}
                            </p>

                            <span
                                class="rounded-full border px-3 py-1 text-xs font-black tracking-[0.12em] uppercase"
                                :class="estatusClasses(pedido.estatus)"
                            >
                                {{ estatusLabel(pedido.estatus) }}
                            </span>
                        </div>

                        <p class="mt-2 text-sm text-neutral-500">
                            Realizado el {{ formatDate(pedido.created_at) }}
                        </p>
                    </div>

                    <Link
                        href="/admin/pedidos"
                        class="inline-flex items-center justify-center rounded-full border border-neutral-200 bg-white px-5 py-3 text-sm font-black text-neutral-700 transition-all duration-200 hover:-translate-y-0.5 hover:bg-neutral-100"
                    >
                        Volver a pedidos
                    </Link>
                </div>

                <div class="mt-6">
                    <div class="mb-3 flex items-center justify-between gap-4">
                        <p
                            class="text-xs font-black tracking-[0.14em] text-neutral-400 uppercase"
                        >
                            Progreso del pedido
                        </p>

                        <p class="text-xs font-black text-neutral-500">
                            {{ progresoPedido }}%
                        </p>
                    </div>

                    <div class="h-3 overflow-hidden rounded-full bg-neutral-100">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-[#30BEEF] to-[#062A5E] transition-all duration-700"
                            :style="{ width: `${progresoPedido}%` }"
                        />
                    </div>

                    <div class="mt-4 grid gap-2 sm:grid-cols-5">
                        <div
                            v-for="paso in timelineSteps"
                            :key="paso"
                            class="rounded-xl border px-2 py-2 text-center text-[11px] font-black tracking-[0.08em] uppercase"
                            :class="
                                timelineSteps.indexOf(paso) <=
                                timelineSteps.indexOf(pedido.estatus)
                                    ? 'border-neutral-900 bg-neutral-900 text-white'
                                    : 'border-neutral-200 bg-white text-neutral-400'
                            "
                        >
                            {{ estatusLabel(paso) }}
                        </div>
                    </div>
                </div>
            </header>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1.45fr)_390px]">
                <div class="space-y-5">
                    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <article
                            class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm"
                        >
                            <p
                                class="text-xs font-black tracking-[0.12em] text-neutral-400 uppercase"
                            >
                                Total
                            </p>
                            <p
                                class="mt-2 text-2xl font-black text-neutral-950"
                            >
                                {{ formatCurrency(pedido.total) }}
                            </p>
                        </article>

                        <article
                            class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm"
                        >
                            <p
                                class="text-xs font-black tracking-[0.12em] text-neutral-400 uppercase"
                            >
                                Productos
                            </p>
                            <p
                                class="mt-2 text-2xl font-black text-neutral-950"
                            >
                                {{ totalPiezas }} pieza(s)
                            </p>
                        </article>

                        <article
                            class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm"
                        >
                            <p
                                class="text-xs font-black tracking-[0.12em] text-neutral-400 uppercase"
                            >
                                Pago
                            </p>
                            <p
                                class="mt-2 text-2xl font-black text-neutral-950"
                            >
                                {{
                                    pagoMasReciente?.estatus
                                        ? estatusLabel(pagoMasReciente.estatus)
                                        : 'Sin registro'
                                }}
                            </p>
                        </article>

                        <article
                            class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm"
                        >
                            <p
                                class="text-xs font-black tracking-[0.12em] text-neutral-400 uppercase"
                            >
                                Entrega
                            </p>
                            <p
                                class="mt-2 text-2xl font-black text-neutral-950"
                            >
                                {{ metodoEntregaLabel }}
                            </p>
                        </article>
                    </section>

                    <section
                        class="rounded-[28px] border border-neutral-200 bg-white p-5 shadow-sm sm:p-6"
                    >
                        <h2 class="text-xl font-black text-neutral-950">
                            Productos comprados
                        </h2>

                        <div class="mt-5 space-y-4">
                            <article
                                v-for="item in pedido.items"
                                :key="item.id"
                                class="rounded-[24px] border border-neutral-200 bg-neutral-50 p-4 transition-all duration-200 hover:border-neutral-300 hover:bg-white"
                            >
                                <div
                                    class="grid gap-4 xl:grid-cols-[88px_minmax(0,1fr)_120px_140px_140px] xl:items-center"
                                >
                                    <div
                                        class="h-[88px] w-[88px] overflow-hidden rounded-2xl border border-neutral-200 bg-white"
                                    >
                                        <img
                                            v-if="item.imagen_url"
                                            :src="item.imagen_url"
                                            :alt="item.nombre"
                                            class="h-full w-full object-cover"
                                        />

                                        <div
                                            v-else
                                            class="flex h-full w-full items-center justify-center bg-gradient-to-br from-neutral-100 to-neutral-200 text-2xl font-black text-neutral-500"
                                        >
                                            {{ inicialProducto(item.nombre) }}
                                        </div>
                                    </div>

                                    <div class="min-w-0">
                                        <p
                                            class="truncate text-lg font-black text-neutral-950"
                                        >
                                            {{ item.nombre }}
                                        </p>

                                        <p class="mt-1 text-sm text-neutral-500">
                                            SKU: {{ item.sku || 'N/A' }}
                                        </p>
                                    </div>

                                    <div>
                                        <p
                                            class="text-[11px] font-black tracking-[0.12em] text-neutral-400 uppercase"
                                        >
                                            Cantidad
                                        </p>
                                        <p
                                            class="mt-2 text-lg font-black text-neutral-950"
                                        >
                                            {{ item.cantidad }}
                                        </p>
                                    </div>

                                    <div>
                                        <p
                                            class="text-[11px] font-black tracking-[0.12em] text-neutral-400 uppercase"
                                        >
                                            Unitario
                                        </p>
                                        <p
                                            class="mt-2 text-lg font-black text-neutral-950"
                                        >
                                            {{
                                                formatCurrency(
                                                    item.precio_unitario,
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <div>
                                        <p
                                            class="text-[11px] font-black tracking-[0.12em] text-neutral-400 uppercase"
                                        >
                                            Subtotal
                                        </p>
                                        <p
                                            class="mt-2 text-lg font-black text-neutral-950"
                                        >
                                            {{ formatCurrency(item.subtotal) }}
                                        </p>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </section>

                    <section class="grid gap-5 xl:grid-cols-2">
                        <article
                            class="rounded-[28px] border border-neutral-200 bg-white p-5 shadow-sm sm:p-6"
                        >
                            <h2 class="text-xl font-black text-neutral-950">
                                Cliente
                            </h2>

                            <div class="mt-5 space-y-3 text-sm">
                                <p>
                                    <span class="font-black text-neutral-500">
                                        Nombre:
                                    </span>
                                    <span
                                        class="ml-2 font-semibold text-neutral-950"
                                    >
                                        {{ pedido.nombre_cliente || 'N/A' }}
                                    </span>
                                </p>

                                <p>
                                    <span class="font-black text-neutral-500">
                                        Correo:
                                    </span>
                                    <span
                                        class="ml-2 font-semibold text-neutral-950"
                                    >
                                        {{ pedido.correo_cliente || 'N/A' }}
                                    </span>
                                </p>

                                <p>
                                    <span class="font-black text-neutral-500">
                                        Teléfono:
                                    </span>
                                    <span
                                        class="ml-2 font-semibold text-neutral-950"
                                    >
                                        {{ pedido.telefono_cliente || 'N/A' }}
                                    </span>
                                </p>

                                <p
                                    v-if="pedido.notas_cliente"
                                    class="rounded-2xl border border-sky-200 bg-sky-50 p-3 text-sky-900"
                                >
                                    {{ pedido.notas_cliente }}
                                </p>
                            </div>
                        </article>

                        <article
                            class="rounded-[28px] border border-neutral-200 bg-white p-5 shadow-sm sm:p-6"
                        >
                            <h2 class="text-xl font-black text-neutral-950">
                                Método de entrega
                            </h2>

                            <div class="mt-5 space-y-3 text-sm">
                                <p>
                                    <span class="font-black text-neutral-500">
                                        Tipo:
                                    </span>
                                    <span
                                        class="ml-2 font-semibold text-neutral-950"
                                    >
                                        {{ metodoEntregaLabel }}
                                    </span>
                                </p>

                                <template v-if="esRecoleccion">
                                    <p>
                                        <span
                                            class="font-black text-neutral-500"
                                        >
                                            Código:
                                        </span>
                                        <span
                                            class="ml-2 font-semibold text-neutral-950"
                                        >
                                            {{
                                                pedido.codigo_recoleccion ||
                                                'Pendiente'
                                            }}
                                        </span>
                                    </p>

                                    <p>
                                        <span
                                            class="font-black text-neutral-500"
                                        >
                                            Listo para recoger:
                                        </span>
                                        <span
                                            class="ml-2 font-semibold text-neutral-950"
                                        >
                                            {{
                                                formatDate(
                                                    pedido.listo_para_recoger_en,
                                                )
                                            }}
                                        </span>
                                    </p>
                                </template>

                                <template v-else>
                                    <p>
                                        <span
                                            class="font-black text-neutral-500"
                                        >
                                            Programada:
                                        </span>
                                        <span
                                            class="ml-2 font-semibold text-neutral-950"
                                        >
                                            {{
                                                formatDate(
                                                    pedido.fecha_entrega_programada,
                                                )
                                            }}
                                        </span>
                                    </p>

                                    <p>
                                        <span
                                            class="font-black text-neutral-500"
                                        >
                                            Salió a entrega:
                                        </span>
                                        <span
                                            class="ml-2 font-semibold text-neutral-950"
                                        >
                                            {{
                                                formatDate(
                                                    pedido.salio_a_entrega_en,
                                                )
                                            }}
                                        </span>
                                    </p>

                                    <p v-if="pedido.zona_entrega">
                                        <span
                                            class="font-black text-neutral-500"
                                        >
                                            Zona:
                                        </span>
                                        <span
                                            class="ml-2 font-semibold text-neutral-950"
                                        >
                                            {{ pedido.zona_entrega }}
                                        </span>
                                    </p>

                                    <p
                                        v-if="pedido.instrucciones_entrega"
                                        class="rounded-2xl border border-sky-200 bg-sky-50 p-3 text-sky-900"
                                    >
                                        {{ pedido.instrucciones_entrega }}
                                    </p>
                                </template>
                            </div>
                        </article>
                    </section>

                    <section
                        v-if="!esRecoleccion"
                        class="rounded-[28px] border border-neutral-200 bg-white p-5 shadow-sm sm:p-6"
                    >
                        <h2 class="text-xl font-black text-neutral-950">
                            Dirección de entrega
                        </h2>

                        <div class="mt-5 space-y-3 text-sm">
                            <p>
                                <span class="font-black text-neutral-500">
                                    Receptor:
                                </span>
                                <span
                                    class="ml-2 font-semibold text-neutral-950"
                                >
                                    {{
                                        pedido.direccion?.nombre_receptor ||
                                        'N/A'
                                    }}
                                </span>
                            </p>

                            <p>
                                <span class="font-black text-neutral-500">
                                    Teléfono:
                                </span>
                                <span
                                    class="ml-2 font-semibold text-neutral-950"
                                >
                                    {{ pedido.direccion?.telefono || 'N/A' }}
                                </span>
                            </p>

                            <p class="leading-6 font-semibold text-neutral-950">
                                {{ direccionCompleta(pedido.direccion) }}
                            </p>

                            <p
                                v-if="pedido.direccion?.referencias"
                                class="text-neutral-600"
                            >
                                <strong>Referencias:</strong>
                                {{ pedido.direccion.referencias }}
                            </p>
                        </div>
                    </section>
                </div>

                <aside class="space-y-5">
                    <section
                        class="rounded-[28px] border border-neutral-200 bg-white p-5 shadow-sm sm:p-6 xl:sticky xl:top-5"
                    >
                        <h2 class="text-xl font-black text-neutral-950">
                            Siguiente acción
                        </h2>

                        <p class="mt-1 text-sm text-neutral-500">
                            Solo se permite avanzar en el flujo correspondiente
                            al método de entrega.
                        </p>

                        <div class="mt-5 space-y-3">
                            <button
                                v-for="estatus in pedido.estatus_siguientes"
                                :key="estatus"
                                type="button"
                                class="w-full rounded-full bg-neutral-950 px-5 py-3 text-sm font-black text-white transition-all duration-200 hover:-translate-y-0.5 hover:bg-neutral-800 disabled:opacity-60"
                                :disabled="form.processing"
                                @click="prepararCambioEstado(estatus)"
                            >
                                Marcar como {{ estatusLabel(estatus) }}
                            </button>

                            <p
                                v-if="!pedido.estatus_siguientes.length"
                                class="rounded-2xl border border-neutral-200 bg-neutral-50 p-4 text-sm font-semibold text-neutral-600"
                            >
                                Este pedido ya no tiene acciones pendientes en
                                el flujo principal.
                            </p>
                        </div>

                        <div
                            class="mt-6 rounded-2xl border border-neutral-200 bg-neutral-50 p-4"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <div>
                                    <p
                                        class="text-xs font-black tracking-[0.12em] text-neutral-400 uppercase"
                                    >
                                        Datos operativos
                                    </p>
                                    <p
                                        class="mt-2 text-sm font-black text-neutral-950"
                                    >
                                        {{ metodoEntregaLabel }}
                                    </p>
                                    <p class="text-xs text-neutral-500">
                                        {{
                                            esRecoleccion
                                                ? pedido.codigo_recoleccion ||
                                                  'Código pendiente'
                                                : pedido.zona_entrega ||
                                                  'Zona sin registrar'
                                        }}
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="rounded-full border border-neutral-200 bg-white px-3 py-2 text-xs font-black text-neutral-700 transition hover:bg-neutral-100"
                                    @click="
                                        editandoEntrega = !editandoEntrega
                                    "
                                >
                                    ✎
                                </button>
                            </div>

                            <div
                                v-if="editandoEntrega"
                                class="mt-4 space-y-3"
                            >
                                <template v-if="!esRecoleccion">
                                    <label class="block">
                                        <span
                                            class="mb-1 block text-xs font-black text-neutral-500 uppercase"
                                        >
                                            Fecha de entrega programada
                                        </span>
                                        <input
                                            v-model="
                                                form.fecha_entrega_programada
                                            "
                                            type="datetime-local"
                                            class="h-11 w-full rounded-xl border border-neutral-200 px-3 text-sm outline-none focus:border-neutral-950 focus:ring-4 focus:ring-neutral-950/10"
                                        />
                                    </label>

                                    <input
                                        v-model="form.zona_entrega"
                                        type="text"
                                        placeholder="Zona de entrega"
                                        class="h-11 w-full rounded-xl border border-neutral-200 px-3 text-sm outline-none focus:border-neutral-950 focus:ring-4 focus:ring-neutral-950/10"
                                    />

                                    <textarea
                                        v-model="form.instrucciones_entrega"
                                        rows="3"
                                        placeholder="Instrucciones de entrega"
                                        class="w-full rounded-xl border border-neutral-200 px-3 py-3 text-sm outline-none focus:border-neutral-950 focus:ring-4 focus:ring-neutral-950/10"
                                    />
                                </template>

                                <textarea
                                    v-model="form.comentario_interno"
                                    rows="3"
                                    placeholder="Comentario interno"
                                    class="w-full rounded-xl border border-neutral-200 px-3 py-3 text-sm outline-none focus:border-neutral-950 focus:ring-4 focus:ring-neutral-950/10"
                                />

                                <button
                                    type="button"
                                    class="w-full rounded-full border border-neutral-900 bg-white px-4 py-2.5 text-sm font-black text-neutral-950 transition hover:bg-neutral-950 hover:text-white"
                                    :disabled="form.processing"
                                    @click="guardarDatosEntrega"
                                >
                                    Guardar datos operativos
                                </button>
                            </div>
                        </div>

                        <div v-if="comentarioVisible" class="mt-5">
                            <label
                                class="mb-2 block text-sm font-black text-neutral-700"
                            >
                                Comentario para historial
                            </label>

                            <textarea
                                v-model="form.comentario"
                                rows="3"
                                placeholder="Ej. Pedido listo para recoger."
                                class="w-full rounded-2xl border border-neutral-200 px-4 py-3 text-sm transition outline-none focus:border-neutral-950 focus:ring-4 focus:ring-neutral-950/10"
                            />

                            <button
                                type="button"
                                class="mt-3 w-full rounded-full bg-neutral-950 px-5 py-3 text-sm font-black text-white transition hover:bg-neutral-800"
                                :disabled="form.processing"
                                @click="guardarCambioEstado"
                            >
                                Confirmar cambio
                            </button>
                        </div>
                    </section>

                    <section
                        class="rounded-[28px] border border-neutral-200 bg-white p-5 shadow-sm sm:p-6"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-xl font-black text-neutral-950">
                                    Pago
                                </h2>

                                <p class="mt-1 text-sm text-neutral-500">
                                    Estado actual del cobro.
                                </p>
                            </div>

                            <span
                                class="rounded-full border px-3 py-1 text-[11px] font-black tracking-[0.12em] uppercase"
                                :class="
                                    estatusClasses(
                                        pagoMasReciente?.estatus || 'pendiente',
                                    )
                                "
                            >
                                {{
                                    pagoMasReciente?.estatus
                                        ? estatusLabel(pagoMasReciente.estatus)
                                        : 'Sin registro'
                                }}
                            </span>
                        </div>

                        <div class="mt-5 space-y-3 text-sm">
                            <p>
                                <span class="font-black text-neutral-500">
                                    Estado:
                                </span>
                                <span
                                    class="ml-2 font-semibold text-neutral-950"
                                >
                                    {{
                                        pagoMasReciente?.estatus
                                            ? estatusLabel(
                                                  pagoMasReciente.estatus,
                                              )
                                            : 'Sin registro'
                                    }}
                                </span>
                            </p>

                            <p>
                                <span class="font-black text-neutral-500">
                                    Método:
                                </span>
                                <span
                                    class="ml-2 font-semibold text-neutral-950"
                                >
                                    {{
                                        pagoMasReciente?.metodo_pago?.nombre ||
                                        'N/A'
                                    }}
                                </span>
                            </p>

                            <p class="break-all">
                                <span class="font-black text-neutral-500">
                                    Referencia:
                                </span>
                                <span
                                    class="ml-2 font-semibold text-neutral-950"
                                >
                                    {{
                                        pagoMasReciente?.referencia_externa ||
                                        'N/A'
                                    }}
                                </span>
                            </p>

                            <p class="break-all">
                                <span class="font-black text-neutral-500">
                                    Autorización:
                                </span>
                                <span
                                    class="ml-2 font-semibold text-neutral-950"
                                >
                                    {{ pagoMasReciente?.autorizacion || 'N/A' }}
                                </span>
                            </p>
                        </div>

                        <div
                            v-if="puedeReintentarPago"
                            class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4"
                        >
                            <p
                                class="text-xs font-black tracking-[0.12em] text-amber-700 uppercase"
                            >
                                Pago pendiente o con error
                            </p>

                            <p class="mt-2 text-sm leading-6 text-amber-900">
                                El cliente puede volver a intentar el pago sin
                                generar otro pedido.
                            </p>

                            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-full border border-amber-300 bg-white px-4 py-2.5 text-sm font-black text-amber-800 transition-all duration-200 hover:-translate-y-0.5 hover:bg-amber-100"
                                    @click="copiarLigaPago"
                                >
                                    Copiar liga de pago
                                </button>

                                <Link
                                    :href="urlPago"
                                    class="inline-flex items-center justify-center rounded-full bg-neutral-950 px-4 py-2.5 text-sm font-black text-white transition-all duration-200 hover:-translate-y-0.5 hover:bg-neutral-800"
                                >
                                    Reintentar pago
                                </Link>
                            </div>
                        </div>
                    </section>

                    <section
                        class="rounded-[28px] border border-neutral-200 bg-white p-5 shadow-sm sm:p-6"
                    >
                        <h2 class="text-xl font-black text-neutral-950">
                            Historial
                        </h2>

                        <div
                            v-if="pedido.historial.length"
                            class="mt-5 space-y-3"
                        >
                            <article
                                v-for="row in pedido.historial"
                                :key="row.id"
                                class="rounded-2xl border border-neutral-200 bg-neutral-50 p-4"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div>
                                        <span
                                            class="rounded-full border px-2.5 py-1 text-[11px] font-black uppercase"
                                            :class="estatusClasses(row.estatus)"
                                        >
                                            {{ estatusLabel(row.estatus) }}
                                        </span>

                                        <p
                                            class="mt-2 text-xs font-semibold text-neutral-500"
                                        >
                                            {{ row.user?.name || 'Sistema' }}
                                        </p>
                                    </div>

                                    <p
                                        class="text-right text-xs text-neutral-400"
                                    >
                                        {{ formatDate(row.created_at) }}
                                    </p>
                                </div>

                                <p
                                    v-if="row.comentario"
                                    class="mt-3 text-sm leading-6 text-neutral-700"
                                >
                                    {{ row.comentario }}
                                </p>
                            </article>
                        </div>

                        <p v-else class="mt-4 text-sm text-neutral-500">
                            Sin movimientos.
                        </p>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</template>