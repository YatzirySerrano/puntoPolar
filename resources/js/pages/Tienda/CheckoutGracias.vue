<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';

declare global {
    interface Window {
        OpenPay?: {
            setId: (id: string) => void;
            setApiKey: (key: string) => void;
            setSandboxMode: (mode: boolean) => void;
            token: {
                create: (
                    data: Record<string, string>,
                    success: (response: { data: { id: string } }) => void,
                    error: (response: {
                        data?: { description?: string; error_code?: string };
                    }) => void,
                ) => void;
            };
            deviceData: {
                setup: (formId?: string, hiddenFieldName?: string) => string;
            };
        };
    }
}

interface PedidoItem {
    id: number;
    nombre: string;
    sku: string;
    cantidad: number;
    precio_unitario: number;
    subtotal: number;
}

interface DireccionPedido {
    nombre_receptor: string;
    telefono: string;
    calle: string;
    numero_exterior: string;
    numero_interior?: string | null;
    colonia: string;
    municipio: string;
    estado: string;
    codigo_postal: string;
    referencias?: string | null;
}

const props = defineProps<{
    pedido: {
        id: number;
        folio: string;
        estatus: string;
        tipo_entrega?: 'recoleccion' | 'entrega_local' | null;
        codigo_recoleccion?: string | null;
        listo_para_recoger_en?: string | null;
        fecha_entrega_programada?: string | null;
        salio_a_entrega_en?: string | null;
        zona_entrega?: string | null;
        instrucciones_entrega?: string | null;
        subtotal: number;
        descuento: number;
        envio: number;
        total: number;
        moneda: string;
        created_at: string | null;
        items: PedidoItem[];
        direccion: DireccionPedido | null;
        pago: {
            id: number;
            estatus: string;
            monto: number;
            referencia_externa?: string | null;
            autorizacion?: string | null;
        } | null;
    };
    openpay: {
        merchant_id: string | null;
        public_key: string | null;
        sandbox: boolean;
    };
}>();

const page = usePage<{
    flash?: {
        success?: string;
        error?: string;
    };
}>();

const card = reactive({
    holder_name: '',
    card_number: '',
    expiration_month: '',
    expiration_year: '',
    cvv2: '',
});

const openpayReady = ref(false);
const paying = ref(false);
const localError = ref('');
const deviceSessionId = ref('');

const flashError = computed(() => page.props.flash?.error ?? '');
const flashSuccess = computed(() => page.props.flash?.success ?? '');
const puedePagar = computed(() => props.pedido.estatus === 'pendiente');

const esRecoleccion = computed(() => {
    return (props.pedido.tipo_entrega ?? 'recoleccion') === 'recoleccion';
});

const metodoEntregaLabel = computed(() => {
    return esRecoleccion.value ? 'Recolección en Punto Polar' : 'Entrega local';
});

const estatusLabel = computed(() => {
    const labels: Record<string, string> = {
        pendiente: 'Pendiente',
        pagado: 'Pagado',
        preparando: 'Preparando',
        listo_para_recoger: 'Listo para recoger',
        salio_a_entrega: 'Salió a entrega',
        entregado: 'Entregado',
        cancelado: 'Cancelado',
        reembolsado: 'Reembolsado',
    };

    return labels[props.pedido.estatus] ?? props.pedido.estatus;
});

const formatearMoneda = (valor: number) => {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
    }).format(Number(valor ?? 0));
};

const formatDate = (value?: string | null) => {
    if (!value) return null;

    return new Date(value).toLocaleString('es-MX', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const loadScript = (src: string) => {
    return new Promise<void>((resolve, reject) => {
        const exists = document.querySelector(`script[src="${src}"]`);

        if (exists) {
            resolve();
            return;
        }

        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error(`No se pudo cargar ${src}`));
        document.head.appendChild(script);
    });
};

const initOpenpay = async () => {
    localError.value = '';

    if (!props.openpay.merchant_id || !props.openpay.public_key) {
        localError.value =
            'Openpay no está configurado. Revisa OPENPAY_MERCHANT_ID y OPENPAY_PUBLIC_KEY en el .env.';
        return;
    }

    try {
        await loadScript('https://openpay.s3.amazonaws.com/openpay.v1.min.js');
        await loadScript(
            'https://openpay.s3.amazonaws.com/openpay-data.v1.min.js',
        );

        if (!window.OpenPay) {
            localError.value = 'No se pudo inicializar Openpay.';
            return;
        }

        window.OpenPay.setId(props.openpay.merchant_id);
        window.OpenPay.setApiKey(props.openpay.public_key);
        window.OpenPay.setSandboxMode(Boolean(props.openpay.sandbox));

        deviceSessionId.value = window.OpenPay.deviceData.setup(
            'openpay-payment-form',
            'device_session_id',
        );
        openpayReady.value = true;
    } catch (error) {
        localError.value =
            error instanceof Error
                ? error.message
                : 'No se pudo cargar Openpay.';
    }
};

const normalizarMes = (value: string) => {
    return value.replace(/\D/g, '').slice(0, 2).padStart(2, '0');
};

const normalizarAnio = (value: string) => {
    return value.replace(/\D/g, '').slice(-2);
};

const pagar = () => {
    localError.value = '';

    if (!window.OpenPay || !openpayReady.value) {
        localError.value =
            'Openpay aún no está listo. Recarga la página e intenta de nuevo.';
        return;
    }

    if (!deviceSessionId.value) {
        localError.value =
            'No se generó el identificador antifraude. Recarga la página e intenta de nuevo.';
        return;
    }

    if (
        !card.holder_name.trim() ||
        !card.card_number.trim() ||
        !card.expiration_month.trim() ||
        !card.expiration_year.trim() ||
        !card.cvv2.trim()
    ) {
        localError.value = 'Completa todos los datos de la tarjeta.';
        return;
    }

    paying.value = true;

    window.OpenPay.token.create(
        {
            holder_name: card.holder_name.trim(),
            card_number: card.card_number.replace(/\s/g, ''),
            expiration_month: normalizarMes(card.expiration_month),
            expiration_year: normalizarAnio(card.expiration_year),
            cvv2: card.cvv2.trim(),
        },
        (response) => {
            const tokenId = response.data.id;

            router.post(
                `/checkout/gracias/${props.pedido.id}/pagar`,
                {
                    token_id: tokenId,
                    device_session_id: deviceSessionId.value,
                },
                {
                    preserveScroll: true,
                    onFinish: () => {
                        paying.value = false;
                    },
                },
            );
        },
        (response) => {
            paying.value = false;
            localError.value =
                response.data?.description ||
                'Openpay rechazó los datos de la tarjeta. Revisa la información e intenta de nuevo.';
        },
    );
};

onMounted(() => {
    if (puedePagar.value) {
        initOpenpay();
    }
});
</script>

<template>
    <PublicLayout>
        <section class="bg-[#f7f8fa]">
            <div class="mx-auto max-w-6xl px-4 py-12 md:px-6 md:py-16">
                <div
                    class="mb-8 rounded-[34px] border border-[#dfdfdf] bg-white p-6 shadow-[0_18px_60px_rgba(9,17,28,0.06)] md:p-8"
                >
                    <div
                        class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between"
                    >
                        <div>
                            <p
                                class="text-xs font-black tracking-[0.24em] text-[#30beef] uppercase"
                            >
                                {{ puedePagar ? 'Pedido creado' : 'Pedido confirmado' }}
                            </p>

                            <h1
                                class="mt-3 text-3xl font-black text-neutral-900 md:text-5xl"
                            >
                                {{ pedido.folio }}
                            </h1>

                            <p class="mt-3 text-sm text-neutral-500">
                                {{
                                    puedePagar
                                        ? 'Revisa el resumen de tu pedido y realiza el pago para confirmar tu compra.'
                                        : 'Tu pago ya fue registrado correctamente. Revisa el resumen y los detalles de entrega de tu pedido.'
                                }}
                            </p>
                        </div>

                        <div
                            class="rounded-full px-5 py-2 text-sm font-black tracking-[0.14em] uppercase"
                            :class="
                                pedido.estatus === 'pagado'
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-amber-100 text-amber-700'
                            "
                        >
                            {{ estatusLabel }}
                        </div>
                    </div>
                </div>

                <div
                    v-if="flashError"
                    class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-bold text-red-600"
                >
                    {{ flashError }}
                </div>

                <div
                    v-if="flashSuccess"
                    class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-700"
                >
                    {{ flashSuccess }}
                </div>

                <div
                    v-if="localError"
                    class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-bold text-red-600"
                >
                    {{ localError }}
                </div>

                <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_420px]">
                    <div class="space-y-8">
                        <section
                            class="rounded-[30px] border border-[#dfdfdf] bg-white p-6 shadow-[0_18px_60px_rgba(9,17,28,0.06)]"
                        >
                            <h2 class="text-xl font-black text-neutral-900">
                                Productos
                            </h2>

                            <div class="mt-4 divide-y divide-[#ececec]">
                                <article
                                    v-for="item in pedido.items"
                                    :key="item.id"
                                    class="py-4 first:pt-0"
                                >
                                    <div
                                        class="flex items-start justify-between gap-4"
                                    >
                                        <div>
                                            <p
                                                class="font-black text-neutral-900"
                                            >
                                                {{ item.nombre }}
                                            </p>
                                            <p
                                                class="mt-1 text-xs font-bold text-neutral-400"
                                            >
                                                SKU: {{ item.sku }}
                                            </p>
                                            <p
                                                class="mt-1 text-sm text-neutral-500"
                                            >
                                                Cantidad: {{ item.cantidad }}
                                            </p>
                                        </div>

                                        <p class="font-black text-neutral-900">
                                            {{ formatearMoneda(item.subtotal) }}
                                        </p>
                                    </div>
                                </article>
                            </div>
                        </section>

                        <section
                            class="rounded-[30px] border border-[#dfdfdf] bg-white p-6 shadow-[0_18px_60px_rgba(9,17,28,0.06)]"
                        >
                            <h2 class="text-xl font-black text-neutral-900">
                                Método de entrega
                            </h2>

                            <div
                                class="mt-4 rounded-2xl border border-[#30beef]/20 bg-[#30beef]/5 p-5 text-sm leading-7 text-neutral-700"
                            >
                                <p class="font-black text-neutral-900">
                                    {{ metodoEntregaLabel }}
                                </p>

                                <template v-if="esRecoleccion">
                                    <p class="mt-2">
                                        Tu pedido será preparado para
                                        recolección en Punto Polar.
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
                                        class="mt-2 text-sm text-neutral-600"
                                    >
                                        Listo desde:
                                        {{
                                            formatDate(
                                                pedido.listo_para_recoger_en,
                                            )
                                        }}
                                    </p>
                                </template>

                                <template v-else>
                                    <p class="mt-2">
                                        Tu pedido será atendido mediante entrega
                                        local propia.
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
                                        v-if="pedido.instrucciones_entrega"
                                        class="mt-2"
                                    >
                                        Indicaciones:
                                        {{ pedido.instrucciones_entrega }}
                                    </p>
                                </template>
                            </div>
                        </section>

                        <section
                            v-if="!esRecoleccion && pedido.direccion"
                            class="rounded-[30px] border border-[#dfdfdf] bg-white p-6 shadow-[0_18px_60px_rgba(9,17,28,0.06)]"
                        >
                            <h2 class="text-xl font-black text-neutral-900">
                                Dirección de entrega
                            </h2>

                            <div class="mt-4 text-sm leading-7 text-neutral-600">
                                <p class="font-black text-neutral-900">
                                    {{ pedido.direccion.nombre_receptor }}
                                </p>
                                <p>{{ pedido.direccion.telefono }}</p>
                                <p>
                                    {{ pedido.direccion.calle }}
                                    {{ pedido.direccion.numero_exterior }}
                                    <span
                                        v-if="pedido.direccion.numero_interior"
                                    >
                                        Int.
                                        {{ pedido.direccion.numero_interior }}
                                    </span>
                                </p>
                                <p>
                                    {{ pedido.direccion.colonia }},
                                    {{ pedido.direccion.municipio }},
                                    {{ pedido.direccion.estado }}
                                </p>
                                <p>CP {{ pedido.direccion.codigo_postal }}</p>
                                <p v-if="pedido.direccion.referencias">
                                    Referencias:
                                    {{ pedido.direccion.referencias }}
                                </p>
                            </div>
                        </section>
                    </div>

                    <aside class="space-y-6">
                        <section
                            class="rounded-[30px] border border-[#dfdfdf] bg-white p-6 shadow-[0_18px_60px_rgba(9,17,28,0.06)]"
                        >
                            <h2 class="text-xl font-black text-neutral-900">
                                Total
                            </h2>

                            <div class="mt-6 space-y-4 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-neutral-500">
                                        Subtotal
                                    </span>
                                    <span class="font-bold text-neutral-900">
                                        {{ formatearMoneda(pedido.subtotal) }}
                                    </span>
                                </div>

                                <div
                                    v-if="pedido.descuento > 0"
                                    class="flex items-center justify-between"
                                >
                                    <span class="text-neutral-500">
                                        Descuento
                                    </span>
                                    <span class="font-bold text-emerald-600">
                                        -{{
                                            formatearMoneda(pedido.descuento)
                                        }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-neutral-500">
                                        Entrega
                                    </span>
                                    <span class="font-bold text-neutral-900">
                                        {{ formatearMoneda(pedido.envio) }}
                                    </span>
                                </div>

                                <div class="border-t border-[#ececec] pt-5">
                                    <div
                                        class="flex items-end justify-between gap-3"
                                    >
                                        <span
                                            class="text-lg font-black text-neutral-900"
                                        >
                                            Total
                                        </span>
                                        <span
                                            class="text-3xl font-black tracking-tight text-neutral-900"
                                        >
                                            {{ formatearMoneda(pedido.total) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section
                            v-if="puedePagar"
                            class="rounded-[30px] border border-[#dfdfdf] bg-white p-6 shadow-[0_18px_60px_rgba(9,17,28,0.06)]"
                        >
                            <h2 class="text-xl font-black text-neutral-900">
                                Pagar con tarjeta
                            </h2>

                            <p class="mt-2 text-sm leading-6 text-neutral-500">
                                Tus datos bancarios se tokenizan directamente
                                con Openpay. No se guardan en el sistema.
                            </p>

                            <form
                                id="openpay-payment-form"
                                class="mt-6 space-y-4"
                                @submit.prevent="pagar"
                            >
                                <input type="hidden" name="device_session_id" />

                                <div>
                                    <label
                                        class="text-sm font-black text-neutral-800"
                                    >
                                        Nombre en la tarjeta
                                    </label>
                                    <input
                                        v-model="card.holder_name"
                                        type="text"
                                        autocomplete="cc-name"
                                        class="mt-2 h-12 w-full rounded-2xl border border-[#d5d5d5] px-4 text-sm transition outline-none focus:border-[#30beef] focus:ring-4 focus:ring-[#30beef]/10"
                                    />
                                </div>

                                <div>
                                    <label
                                        class="text-sm font-black text-neutral-800"
                                    >
                                        Número de tarjeta
                                    </label>
                                    <input
                                        v-model="card.card_number"
                                        type="text"
                                        inputmode="numeric"
                                        autocomplete="cc-number"
                                        placeholder="4111 1111 1111 1111"
                                        class="mt-2 h-12 w-full rounded-2xl border border-[#d5d5d5] px-4 text-sm transition outline-none focus:border-[#30beef] focus:ring-4 focus:ring-[#30beef]/10"
                                    />
                                </div>

                                <div class="grid grid-cols-3 gap-3">
                                    <div>
                                        <label
                                            class="text-sm font-black text-neutral-800"
                                        >
                                            Mes
                                        </label>
                                        <input
                                            v-model="card.expiration_month"
                                            type="text"
                                            inputmode="numeric"
                                            autocomplete="cc-exp-month"
                                            placeholder="12"
                                            class="mt-2 h-12 w-full rounded-2xl border border-[#d5d5d5] px-4 text-sm transition outline-none focus:border-[#30beef] focus:ring-4 focus:ring-[#30beef]/10"
                                        />
                                    </div>

                                    <div>
                                        <label
                                            class="text-sm font-black text-neutral-800"
                                        >
                                            Año
                                        </label>
                                        <input
                                            v-model="card.expiration_year"
                                            type="text"
                                            inputmode="numeric"
                                            autocomplete="cc-exp-year"
                                            placeholder="28"
                                            class="mt-2 h-12 w-full rounded-2xl border border-[#d5d5d5] px-4 text-sm transition outline-none focus:border-[#30beef] focus:ring-4 focus:ring-[#30beef]/10"
                                        />
                                    </div>

                                    <div>
                                        <label
                                            class="text-sm font-black text-neutral-800"
                                        >
                                            CVV
                                        </label>
                                        <input
                                            v-model="card.cvv2"
                                            type="password"
                                            inputmode="numeric"
                                            autocomplete="cc-csc"
                                            placeholder="123"
                                            class="mt-2 h-12 w-full rounded-2xl border border-[#d5d5d5] px-4 text-sm transition outline-none focus:border-[#30beef] focus:ring-4 focus:ring-[#30beef]/10"
                                        />
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    class="w-full rounded-full bg-gradient-to-r from-[#30BEEF] to-[#062A5E] px-4 py-3.5 font-black text-white transition-all duration-300 hover:-translate-y-0.5 hover:opacity-90 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="paying || !openpayReady"
                                >
                                    {{
                                        paying
                                            ? 'Procesando pago...'
                                            : openpayReady
                                              ? 'Pagar ahora'
                                              : 'Cargando Openpay...'
                                    }}
                                </button>
                            </form>
                        </section>

                        <section
                            v-else
                            class="rounded-[30px] border border-emerald-200 bg-emerald-50 p-6"
                        >
                            <h2 class="text-xl font-black text-emerald-800">
                                Pedido pagado
                            </h2>

                            <p class="mt-2 text-sm leading-6 text-emerald-700">
                                El pago de este pedido ya fue registrado
                                correctamente.
                            </p>

                            <Link
                                href="/mi-cuenta/pedidos"
                                class="mt-5 inline-flex w-full items-center justify-center rounded-full bg-emerald-600 px-4 py-3 font-black text-white transition hover:bg-emerald-700"
                            >
                                Ver mis pedidos
                            </Link>
                        </section>
                    </aside>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>