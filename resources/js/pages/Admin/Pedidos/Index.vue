<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { computed, onMounted, ref, watch } from 'vue';
import { CalendarIcon, Search, X } from 'lucide-vue-next';
import { useCurrency } from '@/composables/useCurrency';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import type { DateValue } from '@internationalized/date';
import { getLocalTimeZone, today } from '@internationalized/date';

interface PedidoItemPreview {
    id: number;
    nombre: string;
    sku?: string | null;
    cantidad: number;
    precio_unitario?: number;
    subtotal?: number;
    imagen_url?: string | null;
}

interface PagoRow {
    id: number;
    estatus: string;
    monto?: number | string;
    referencia_externa?: string | null;
    autorizacion?: string | null;
    pagado_en?: string | null;
    metodo_pago?: {
        nombre: string;
        clave: string;
    } | null;
}

interface Pedido {
    id: number;
    folio: string;
    estatus: string;
    total: number | string;
    nombre_cliente: string;
    correo_cliente: string;
    telefono_cliente?: string | null;
    paqueteria?: string | null;
    numero_guia?: string | null;
    comentario_interno?: string | null;
    items: PedidoItemPreview[];
    pagos?: PagoRow[];
    created_at?: string | null;
}

const props = defineProps<{
    pedidos: {
        data: Pedido[];
        links?: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    stats: {
        total_pedidos: number;
        monto_visible: number;
        pendientes: number;
        en_operacion: number;
        entregados: number;
    };
    estatusDisponibles: string[];
    pagosDisponibles: string[];
    filters: {
        estatus?: string;
        pago?: string;
        buscar?: string;
        fecha_desde?: string;
        fecha_hasta?: string;
    };
}>();

const page = usePage<{
    flash?: {
        success?: string;
        error?: string;
    };
}>();

const { formatCurrency } = useCurrency();

const filterForm = useForm({
    buscar: props.filters.buscar || '',
    estatus: props.filters.estatus || '',
    pago: props.filters.pago || '',
    fecha_desde: props.filters.fecha_desde || '',
    fecha_hasta: props.filters.fecha_hasta || '',
});

const fechaDesdeValue = ref<DateValue | undefined>(
    props.filters.fecha_desde ? today(getLocalTimeZone()) : undefined,
);

const fechaHastaValue = ref<DateValue | undefined>(
    props.filters.fecha_hasta ? today(getLocalTimeZone()) : undefined,
);

const estaBuscando = ref(false);
let searchTimeout: number | undefined;

const hayFiltrosActivos = computed(() =>
    Boolean(
        filterForm.buscar ||
        filterForm.estatus ||
        filterForm.pago ||
        filterForm.fecha_desde ||
        filterForm.fecha_hasta,
    ),
);

const estadosPrincipales = computed(() => ['', ...props.estatusDisponibles]);

const mostrarFlash = () => {
    const ok = page.props.flash?.success;
    const err = page.props.flash?.error;

    if (ok) {
        Swal.fire({
            icon: 'success',
            title: 'Listo',
            text: String(ok),
            timer: 1600,
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

watch(
    () => ({
        buscar: filterForm.buscar,
        estatus: filterForm.estatus,
        pago: filterForm.pago,
        fecha_desde: filterForm.fecha_desde,
        fecha_hasta: filterForm.fecha_hasta,
    }),
    () => {
        window.clearTimeout(searchTimeout);

        estaBuscando.value = true;

        searchTimeout = window.setTimeout(() => {
            router.get(
                '/admin/pedidos',
                {
                    buscar: filterForm.buscar || undefined,
                    estatus: filterForm.estatus || undefined,
                    pago: filterForm.pago || undefined,
                    fecha_desde: filterForm.fecha_desde || undefined,
                    fecha_hasta: filterForm.fecha_hasta || undefined,
                },
                {
                    preserveState: true,
                    replace: true,
                    preserveScroll: true,
                    onFinish: () => {
                        estaBuscando.value = false;
                    },
                },
            );
        }, 350);
    },
    { deep: true },
);

watch(fechaDesdeValue, (value) => {
    filterForm.fecha_desde = value ? value.toString() : '';
});

watch(fechaHastaValue, (value) => {
    filterForm.fecha_hasta = value ? value.toString() : '';
});

const estatusLabel = (estatus: string) => {
    const labels: Record<string, string> = {
        '': 'Todos',
        pendiente: 'Pendiente',
        pagado: 'Pagado',
        preparando: 'Preparando',
        enviado: 'Enviado',
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
        enviado: 'border-indigo-200 bg-indigo-50 text-indigo-700',
        entregado: 'border-lime-200 bg-lime-50 text-lime-700',
        cancelado: 'border-red-200 bg-red-50 text-red-700',
        reembolsado: 'border-purple-200 bg-purple-50 text-purple-700',
        aprobado: 'border-emerald-200 bg-emerald-50 text-emerald-700',
        rechazado: 'border-red-200 bg-red-50 text-red-700',
        error: 'border-red-200 bg-red-50 text-red-700',
        procesando: 'border-blue-200 bg-blue-50 text-blue-700',
    };

    return classes[estatus] ?? 'border-neutral-200 bg-white text-neutral-600';
};

const pagoMasReciente = (pedido: Pedido) => {
    if (!pedido.pagos?.length) return null;

    return [...pedido.pagos].sort((a, b) => b.id - a.id)[0];
};

const totalItems = (pedido: Pedido) => {
    return pedido.items.reduce(
        (acc, item) => acc + Number(item.cantidad || 0),
        0,
    );
};

const primerItem = (pedido: Pedido) => {
    return pedido.items[0] ?? null;
};

const resumenItems = (pedido: Pedido) => {
    const item = primerItem(pedido);

    if (!item) {
        return 'Sin productos registrados';
    }

    const extra =
        pedido.items.length > 1 ? ` +${pedido.items.length - 1} más` : '';

    return `${item.nombre}${extra}`;
};

const inicialProducto = (pedido: Pedido) => {
    const item = primerItem(pedido);

    return item?.nombre?.charAt(0)?.toUpperCase() || 'P';
};

const formatDate = (value?: string | null) => {
    if (!value) return 'Sin fecha';

    return new Date(value).toLocaleDateString('es-MX', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const limpiarFiltros = () => {
    filterForm.buscar = '';
    filterForm.estatus = '';
    filterForm.pago = '';
    filterForm.fecha_desde = '';
    filterForm.fecha_hasta = '';
    fechaDesdeValue.value = undefined;
    fechaHastaValue.value = undefined;
};

const seleccionarEstado = (estatus: string) => {
    filterForm.estatus = estatus;
};

const seleccionarPago = (pago: string) => {
    filterForm.pago = filterForm.pago === pago ? '' : pago;
};
</script>

<template>
    <Head title="Admin · Pedidos" />

    <div class="min-h-screen bg-[#f4f6f8] px-4 py-4 sm:px-5 lg:px-6">
        <div class="w-full space-y-4">
            <section
                class="rounded-[28px] border border-neutral-200 bg-white p-5 shadow-sm sm:p-6"
            >
                <div
                    class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between"
                >
                    <div class="min-w-0">
                        <h1
                            class="mt-2 text-3xl font-black tracking-tight text-neutral-950"
                        >
                            Pedidos
                        </h1>
                    </div>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    <div
                        class="rounded-2xl border border-neutral-200 bg-neutral-50 p-4"
                    >
                        <p
                            class="text-xs font-black tracking-[0.14em] text-neutral-400 uppercase"
                        >
                            Pedidos
                        </p>
                        <p class="mt-2 text-3xl font-black text-neutral-950">
                            {{ stats.total_pedidos }}
                        </p>
                    </div>

                    <div
                        class="rounded-2xl border border-neutral-200 bg-neutral-50 p-4"
                    >
                        <p
                            class="text-xs font-black tracking-[0.14em] text-neutral-400 uppercase"
                        >
                            Monto visible
                        </p>
                        <p class="mt-2 text-3xl font-black text-neutral-950">
                            {{ formatCurrency(stats.monto_visible) }}
                        </p>
                    </div>

                    <div
                        class="rounded-2xl border border-amber-200 bg-amber-50 p-4"
                    >
                        <p
                            class="text-xs font-black tracking-[0.14em] text-amber-700 uppercase"
                        >
                            Pendientes
                        </p>
                        <p class="mt-2 text-3xl font-black text-amber-900">
                            {{ stats.pendientes }}
                        </p>
                    </div>

                    <div
                        class="rounded-2xl border border-sky-200 bg-sky-50 p-4"
                    >
                        <p
                            class="text-xs font-black tracking-[0.14em] text-sky-700 uppercase"
                        >
                            En proceso
                        </p>
                        <p class="mt-2 text-3xl font-black text-sky-900">
                            {{ stats.en_operacion }}
                        </p>
                    </div>

                    <div
                        class="rounded-2xl border border-lime-200 bg-lime-50 p-4"
                    >
                        <p
                            class="text-xs font-black tracking-[0.14em] text-lime-700 uppercase"
                        >
                            Entregados
                        </p>
                        <p class="mt-2 text-3xl font-black text-lime-900">
                            {{ stats.entregados }}
                        </p>
                    </div>
                </div>
            </section>

            <section
                class="rounded-[28px] border border-neutral-200 bg-white p-4 shadow-sm sm:p-5"
            >
                <div
                    class="grid gap-3 xl:grid-cols-[minmax(280px,1fr)_auto_auto_auto] xl:items-center"
                >
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-4 h-4 w-4 -translate-y-1/2 text-neutral-400"
                        />
                        <input
                            v-model="filterForm.buscar"
                            type="text"
                            placeholder="Buscar por folio, cliente, correo o teléfono"
                            class="h-12 w-full rounded-2xl border border-neutral-200 bg-neutral-50 pr-4 pl-11 text-sm transition outline-none focus:border-neutral-950 focus:bg-white focus:ring-4 focus:ring-neutral-950/10"
                        />
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Popover>
                            <PopoverTrigger as-child>
                                <Button
                                    variant="outline"
                                    class="h-12 rounded-2xl border-neutral-200 px-4 text-sm font-black"
                                >
                                    <CalendarIcon class="mr-2 h-4 w-4" />
                                    {{ filterForm.fecha_desde || 'Desde' }}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0" align="end">
                                <Calendar
                                    v-model="fechaDesdeValue"
                                    initial-focus
                                />
                            </PopoverContent>
                        </Popover>

                        <Popover>
                            <PopoverTrigger as-child>
                                <Button
                                    variant="outline"
                                    class="h-12 rounded-2xl border-neutral-200 px-4 text-sm font-black"
                                >
                                    <CalendarIcon class="mr-2 h-4 w-4" />
                                    {{ filterForm.fecha_hasta || 'Hasta' }}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0" align="end">
                                <Calendar
                                    v-model="fechaHastaValue"
                                    initial-focus
                                />
                            </PopoverContent>
                        </Popover>
                    </div>

                    <button
                        v-if="hayFiltrosActivos"
                        type="button"
                        class="inline-flex h-12 items-center justify-center rounded-2xl border border-neutral-200 bg-white px-4 text-sm font-black text-neutral-700 transition hover:bg-neutral-100"
                        @click="limpiarFiltros"
                    >
                        <X class="mr-2 h-4 w-4" />
                        Limpiar
                    </button>
                </div>

                <div class="mt-4 flex flex-col gap-3">
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="estatus in estadosPrincipales"
                            :key="estatus || 'todos'"
                            type="button"
                            class="rounded-full border px-4 py-2 text-xs font-black tracking-[0.12em] uppercase transition-all duration-200"
                            :class="
                                filterForm.estatus === estatus
                                    ? 'border-neutral-950 bg-neutral-950 text-white'
                                    : 'border-neutral-200 bg-white text-neutral-600 hover:border-neutral-300 hover:bg-neutral-50'
                            "
                            @click="seleccionarEstado(estatus)"
                        >
                            {{ estatusLabel(estatus) }}
                        </button>
                    </div>

                    <div
                        class="flex flex-wrap items-center gap-2 border-t border-neutral-100 pt-3"
                    >
                        <span
                            class="text-xs font-black tracking-[0.14em] text-neutral-400 uppercase"
                        >
                            Pago
                        </span>

                        <button
                            v-for="pago in pagosDisponibles"
                            :key="pago"
                            type="button"
                            class="rounded-full border px-3 py-1.5 text-[11px] font-black tracking-[0.12em] uppercase transition-all duration-200"
                            :class="
                                filterForm.pago === pago
                                    ? 'border-neutral-950 bg-neutral-950 text-white'
                                    : 'border-neutral-200 bg-white text-neutral-500 hover:bg-neutral-50'
                            "
                            @click="seleccionarPago(pago)"
                        >
                            {{ estatusLabel(pago) }}
                        </button>
                    </div>
                </div>
            </section>

            <section
                v-if="pedidos.data.length"
                class="grid gap-4 xl:grid-cols-2 2xl:grid-cols-3"
            >
                <article
                    v-for="pedido in pedidos.data"
                    :key="pedido.id"
                    class="rounded-[28px] border border-neutral-200 bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-neutral-300 hover:shadow-md"
                >
                    <div class="flex gap-4">
                        <div
                            class="h-24 w-24 shrink-0 overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-100"
                        >
                            <img
                                v-if="primerItem(pedido)?.imagen_url"
                                :src="primerItem(pedido)?.imagen_url || ''"
                                :alt="primerItem(pedido)?.nombre || 'Producto'"
                                class="h-full w-full object-cover"
                            />

                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center bg-gradient-to-br from-neutral-100 to-neutral-200 text-2xl font-black text-neutral-500"
                            >
                                {{ inicialProducto(pedido) }}
                            </div>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p
                                    class="truncate text-xl font-black tracking-tight text-neutral-950"
                                >
                                    {{ pedido.folio }}
                                </p>

                                <span
                                    class="rounded-full border px-2.5 py-1 text-[11px] font-black tracking-[0.12em] uppercase"
                                    :class="estatusClasses(pedido.estatus)"
                                >
                                    {{ estatusLabel(pedido.estatus) }}
                                </span>
                            </div>

                            <p
                                class="mt-2 truncate text-sm font-black text-neutral-800"
                            >
                                {{ pedido.nombre_cliente }}
                            </p>

                            <p class="truncate text-sm text-neutral-500">
                                {{ pedido.correo_cliente }}
                            </p>

                            <p
                                class="mt-3 truncate text-sm font-semibold text-neutral-950"
                            >
                                {{ resumenItems(pedido) }}
                            </p>

                            <p class="mt-1 text-xs text-neutral-500">
                                {{ totalItems(pedido) }} pieza(s) ·
                                {{ formatDate(pedido.created_at) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-3">
                        <div
                            class="rounded-2xl border border-neutral-200 bg-neutral-50 p-3"
                        >
                            <p
                                class="text-[11px] font-black tracking-[0.12em] text-neutral-400 uppercase"
                            >
                                Pago
                            </p>

                            <p
                                class="mt-2 text-sm font-black"
                                :class="
                                    pagoMasReciente(pedido)?.estatus ===
                                    'aprobado'
                                        ? 'text-emerald-700'
                                        : pagoMasReciente(pedido)?.estatus ===
                                                'rechazado' ||
                                            pagoMasReciente(pedido)?.estatus ===
                                                'error'
                                          ? 'text-red-700'
                                          : 'text-neutral-900'
                                "
                            >
                                {{
                                    pagoMasReciente(pedido)?.estatus
                                        ? estatusLabel(
                                              pagoMasReciente(pedido)!.estatus,
                                          )
                                        : 'Sin registro'
                                }}
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-neutral-200 bg-neutral-50 p-3"
                        >
                            <p
                                class="text-[11px] font-black tracking-[0.12em] text-neutral-400 uppercase"
                            >
                                Total
                            </p>

                            <p class="mt-2 text-sm font-black text-neutral-950">
                                {{ formatCurrency(pedido.total) }}
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-neutral-200 bg-neutral-50 p-3"
                        >
                            <p
                                class="text-[11px] font-black tracking-[0.12em] text-neutral-400 uppercase"
                            >
                                Envío
                            </p>

                            <p
                                class="mt-2 truncate text-sm font-black text-neutral-950"
                            >
                                {{ pedido.paqueteria || 'Pendiente' }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="pedido.numero_guia"
                        class="mt-3 rounded-2xl border border-indigo-200 bg-indigo-50 p-3"
                    >
                        <p
                            class="text-[11px] font-black tracking-[0.12em] text-indigo-700 uppercase"
                        >
                            Guía
                        </p>
                        <p
                            class="mt-1 text-sm font-semibold break-all text-indigo-950"
                        >
                            {{ pedido.numero_guia }}
                        </p>
                    </div>

                    <div class="mt-5">
                        <Link
                            :href="`/admin/pedidos/${pedido.id}`"
                            class="inline-flex w-full items-center justify-center rounded-full bg-neutral-950 px-5 py-3 text-sm font-black text-white transition-all duration-200 hover:-translate-y-0.5 hover:bg-neutral-800"
                        >
                            Gestionar pedido
                        </Link>
                    </div>
                </article>
            </section>

            <section
                v-else
                class="rounded-[28px] border border-dashed border-neutral-300 bg-white p-10 text-center shadow-sm"
            >
                <p class="text-lg font-black text-neutral-950">
                    No hay pedidos para mostrar
                </p>

                <p class="mt-2 text-sm text-neutral-500">
                    Ajusta los filtros para consultar otros pedidos.
                </p>

                <button
                    type="button"
                    class="mt-5 rounded-full bg-neutral-950 px-5 py-3 text-sm font-black text-white transition hover:bg-neutral-800"
                    @click="limpiarFiltros"
                >
                    Limpiar filtros
                </button>
            </section>
        </div>
    </div>
</template>
