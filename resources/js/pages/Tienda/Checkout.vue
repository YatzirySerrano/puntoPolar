<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface ItemCheckout {
    producto_id: number;
    nombre: string;
    slug: string;
    sku: string;
    imagen?: string | null;
    precio: number;
    precio_original?: number | null;
    cantidad: number;
    stock: number;
    subtotal: number;
}

interface Direccion {
    id: number;
    alias: string;
    nombre_receptor: string;
    telefono: string;
    calle: string;
    numero_exterior: string;
    numero_interior?: string | null;
    colonia: string;
    municipio: string;
    estado: string;
    pais: string;
    codigo_postal: string;
    referencias?: string | null;
    predeterminada: boolean;
}

const props = defineProps<{
    items: ItemCheckout[];
    cupon: {
        codigo: string;
        nombre: string;
        tipo: string;
        valor: number;
        descuento_aplicado: number;
    } | null;
    resumen: {
        subtotal: number;
        envio: number;
        descuento: number;
        descuento_ofertas: number;
        descuento_cupon: number;
        total: number;
        total_productos: number;
    };
    direcciones: Direccion[];
    cliente: {
        nombre: string;
        correo: string;
    };
}>();

const page = usePage<{
    flash?: {
        success?: string;
        error?: string;
    };
}>();

const procesando = ref(false);

const direccionPredeterminada = computed(() => {
    return (
        props.direcciones.find((direccion) => direccion.predeterminada) ??
        props.direcciones[0] ??
        null
    );
});

const usarDireccionExistente = ref(Boolean(direccionPredeterminada.value));
const direccionId = ref<number | null>(
    direccionPredeterminada.value?.id ?? null,
);

const form = ref({
    nombre_cliente: props.cliente.nombre ?? '',
    correo_cliente: props.cliente.correo ?? '',
    telefono_cliente: '',

    notas_cliente: '',

    alias: 'Casa',
    nombre_receptor: props.cliente.nombre ?? '',
    telefono: '',
    calle: '',
    numero_exterior: '',
    numero_interior: '',
    colonia: '',
    municipio: '',
    estado: '',
    pais: 'México',
    codigo_postal: '',
    referencias: '',
});

const errors = computed(() => page.props.errors ?? {});
const flashError = computed(() => page.props.flash?.error ?? '');
const flashSuccess = computed(() => page.props.flash?.success ?? '');

const formatearMoneda = (valor: number) => {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
    }).format(Number(valor ?? 0));
};

const direccionCompleta = (direccion: Direccion) => {
    return [
        `${direccion.calle} ${direccion.numero_exterior}`,
        direccion.numero_interior ? `Int. ${direccion.numero_interior}` : '',
        direccion.colonia,
        direccion.municipio,
        direccion.estado,
        `CP ${direccion.codigo_postal}`,
    ]
        .filter(Boolean)
        .join(', ');
};

const seleccionarDireccion = (id: number) => {
    direccionId.value = id;
    usarDireccionExistente.value = true;
};

const usarNuevaDireccion = () => {
    usarDireccionExistente.value = false;
    direccionId.value = null;
};

const confirmarPedido = () => {
    procesando.value = true;

    router.post(
        '/checkout',
        {
            ...form.value,
            usar_direccion_existente: usarDireccionExistente.value,
            direccion_id: direccionId.value,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                procesando.value = false;
            },
        },
    );
};
</script>

<template>
    <PublicLayout>
        <section class="bg-[#f7f8fa]">
            <div class="mx-auto max-w-7xl px-4 py-10 md:px-6 md:py-12">
                <div
                    class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div>
                        <p
                            class="text-xs font-black tracking-[0.22em] text-[#30beef] uppercase"
                        >
                            Tienda Mr Lana
                        </p>

                        <h1
                            class="mt-2 text-3xl font-black tracking-tight text-neutral-900 md:text-5xl"
                        >
                            Checkout
                        </h1>

                        <p
                            class="mt-3 max-w-2xl text-sm leading-6 text-neutral-500 md:text-base"
                        >
                            Confirma tus datos y dirección de envío. En el
                            siguiente paso conectaremos el pago con Openpay.
                        </p>
                    </div>

                    <Link
                        href="/carrito"
                        class="inline-flex items-center justify-center rounded-full border border-[#30beef] bg-white px-6 py-3 text-sm font-black text-[#30beef] shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#30beef] hover:text-white hover:shadow-md"
                    >
                        Volver al carrito
                    </Link>
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

                <form
                    class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_410px]"
                    @submit.prevent="confirmarPedido"
                >
                    <div class="space-y-8">
                        <section
                            class="rounded-[30px] border border-[#dfdfdf] bg-white p-6 shadow-[0_18px_60px_rgba(9,17,28,0.06)] md:p-8"
                        >
                            <h2 class="text-2xl font-black text-neutral-900">
                                Datos de contacto
                            </h2>

                            <div class="mt-6 grid gap-5 md:grid-cols-2">
                                <div>
                                    <label
                                        class="text-sm font-black text-neutral-800"
                                        >Nombre completo</label
                                    >
                                    <input
                                        v-model="form.nombre_cliente"
                                        type="text"
                                        class="mt-2 h-12 w-full rounded-2xl border border-[#d5d5d5] px-4 text-sm transition outline-none focus:border-[#30beef] focus:ring-4 focus:ring-[#30beef]/10"
                                    />
                                    <p
                                        v-if="errors.nombre_cliente"
                                        class="mt-2 text-xs font-bold text-red-500"
                                    >
                                        {{ errors.nombre_cliente }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="text-sm font-black text-neutral-800"
                                        >Correo electrónico</label
                                    >
                                    <input
                                        v-model="form.correo_cliente"
                                        type="email"
                                        class="mt-2 h-12 w-full rounded-2xl border border-[#d5d5d5] px-4 text-sm transition outline-none focus:border-[#30beef] focus:ring-4 focus:ring-[#30beef]/10"
                                    />
                                    <p
                                        v-if="errors.correo_cliente"
                                        class="mt-2 text-xs font-bold text-red-500"
                                    >
                                        {{ errors.correo_cliente }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="text-sm font-black text-neutral-800"
                                        >Teléfono</label
                                    >
                                    <input
                                        v-model="form.telefono_cliente"
                                        type="text"
                                        class="mt-2 h-12 w-full rounded-2xl border border-[#d5d5d5] px-4 text-sm transition outline-none focus:border-[#30beef] focus:ring-4 focus:ring-[#30beef]/10"
                                    />
                                    <p
                                        v-if="errors.telefono_cliente"
                                        class="mt-2 text-xs font-bold text-red-500"
                                    >
                                        {{ errors.telefono_cliente }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="text-sm font-black text-neutral-800"
                                        >Notas del pedido</label
                                    >
                                    <input
                                        v-model="form.notas_cliente"
                                        type="text"
                                        placeholder="Opcional"
                                        class="mt-2 h-12 w-full rounded-2xl border border-[#d5d5d5] px-4 text-sm transition outline-none focus:border-[#30beef] focus:ring-4 focus:ring-[#30beef]/10"
                                    />
                                </div>
                            </div>
                        </section>

                        <section
                            class="rounded-[30px] border border-[#dfdfdf] bg-white p-6 shadow-[0_18px_60px_rgba(9,17,28,0.06)] md:p-8"
                        >
                            <div
                                class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
                            >
                                <div>
                                    <h2
                                        class="text-2xl font-black text-neutral-900"
                                    >
                                        Dirección de envío
                                    </h2>
                                    <p class="mt-1 text-sm text-neutral-500">
                                        Selecciona una dirección guardada o
                                        captura una nueva.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="rounded-full border border-[#7dd03c] px-5 py-2.5 text-sm font-black text-[#4ca51d] transition hover:bg-[#7dd03c] hover:text-white"
                                    @click="usarNuevaDireccion"
                                >
                                    Nueva dirección
                                </button>
                            </div>

                            <div
                                v-if="direcciones.length"
                                class="mt-6 grid gap-4 md:grid-cols-2"
                            >
                                <button
                                    v-for="direccion in direcciones"
                                    :key="direccion.id"
                                    type="button"
                                    class="rounded-[24px] border p-5 text-left transition"
                                    :class="
                                        direccionId === direccion.id &&
                                        usarDireccionExistente
                                            ? 'border-[#30beef] bg-[#30beef]/5 ring-4 ring-[#30beef]/10'
                                            : 'border-[#e5e5e5] bg-white hover:border-[#30beef]'
                                    "
                                    @click="seleccionarDireccion(direccion.id)"
                                >
                                    <div
                                        class="flex items-center justify-between gap-3"
                                    >
                                        <p class="font-black text-neutral-900">
                                            {{ direccion.alias }}
                                        </p>

                                        <span
                                            v-if="direccion.predeterminada"
                                            class="rounded-full bg-[#7dd03c]/15 px-3 py-1 text-[11px] font-black tracking-[0.14em] text-[#4ca51d] uppercase"
                                        >
                                            Principal
                                        </span>
                                    </div>

                                    <p
                                        class="mt-2 text-sm font-bold text-neutral-700"
                                    >
                                        {{ direccion.nombre_receptor }}
                                    </p>

                                    <p
                                        class="mt-1 text-sm leading-6 text-neutral-500"
                                    >
                                        {{ direccionCompleta(direccion) }}
                                    </p>
                                </button>
                            </div>

                            <div
                                v-if="!usarDireccionExistente"
                                class="mt-8 rounded-[26px] border border-[#e5e5e5] bg-[#fafafa] p-5 md:p-6"
                            >
                                <h3 class="text-lg font-black text-neutral-900">
                                    Capturar nueva dirección
                                </h3>

                                <div class="mt-5 grid gap-5 md:grid-cols-2">
                                    <div>
                                        <label
                                            class="text-sm font-black text-neutral-800"
                                            >Alias</label
                                        >
                                        <input
                                            v-model="form.alias"
                                            type="text"
                                            class="mt-2 h-12 w-full rounded-2xl border border-[#d5d5d5] px-4 text-sm outline-none focus:border-[#30beef] focus:ring-4 focus:ring-[#30beef]/10"
                                        />
                                    </div>

                                    <div>
                                        <label
                                            class="text-sm font-black text-neutral-800"
                                            >Nombre de quien recibe</label
                                        >
                                        <input
                                            v-model="form.nombre_receptor"
                                            type="text"
                                            class="mt-2 h-12 w-full rounded-2xl border border-[#d5d5d5] px-4 text-sm outline-none focus:border-[#30beef] focus:ring-4 focus:ring-[#30beef]/10"
                                        />
                                        <p
                                            v-if="errors.nombre_receptor"
                                            class="mt-2 text-xs font-bold text-red-500"
                                        >
                                            {{ errors.nombre_receptor }}
                                        </p>
                                    </div>

                                    <div>
                                        <label
                                            class="text-sm font-black text-neutral-800"
                                            >Teléfono de recepción</label
                                        >
                                        <input
                                            v-model="form.telefono"
                                            type="text"
                                            class="mt-2 h-12 w-full rounded-2xl border border-[#d5d5d5] px-4 text-sm outline-none focus:border-[#30beef] focus:ring-4 focus:ring-[#30beef]/10"
                                        />
                                        <p
                                            v-if="errors.telefono"
                                            class="mt-2 text-xs font-bold text-red-500"
                                        >
                                            {{ errors.telefono }}
                                        </p>
                                    </div>

                                    <div>
                                        <label
                                            class="text-sm font-black text-neutral-800"
                                            >Código postal</label
                                        >
                                        <input
                                            v-model="form.codigo_postal"
                                            type="text"
                                            class="mt-2 h-12 w-full rounded-2xl border border-[#d5d5d5] px-4 text-sm outline-none focus:border-[#30beef] focus:ring-4 focus:ring-[#30beef]/10"
                                        />
                                        <p
                                            v-if="errors.codigo_postal"
                                            class="mt-2 text-xs font-bold text-red-500"
                                        >
                                            {{ errors.codigo_postal }}
                                        </p>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label
                                            class="text-sm font-black text-neutral-800"
                                            >Calle</label
                                        >
                                        <input
                                            v-model="form.calle"
                                            type="text"
                                            class="mt-2 h-12 w-full rounded-2xl border border-[#d5d5d5] px-4 text-sm outline-none focus:border-[#30beef] focus:ring-4 focus:ring-[#30beef]/10"
                                        />
                                        <p
                                            v-if="errors.calle"
                                            class="mt-2 text-xs font-bold text-red-500"
                                        >
                                            {{ errors.calle }}
                                        </p>
                                    </div>

                                    <div>
                                        <label
                                            class="text-sm font-black text-neutral-800"
                                            >Número exterior</label
                                        >
                                        <input
                                            v-model="form.numero_exterior"
                                            type="text"
                                            class="mt-2 h-12 w-full rounded-2xl border border-[#d5d5d5] px-4 text-sm outline-none focus:border-[#30beef] focus:ring-4 focus:ring-[#30beef]/10"
                                        />
                                        <p
                                            v-if="errors.numero_exterior"
                                            class="mt-2 text-xs font-bold text-red-500"
                                        >
                                            {{ errors.numero_exterior }}
                                        </p>
                                    </div>

                                    <div>
                                        <label
                                            class="text-sm font-black text-neutral-800"
                                            >Número interior</label
                                        >
                                        <input
                                            v-model="form.numero_interior"
                                            type="text"
                                            placeholder="Opcional"
                                            class="mt-2 h-12 w-full rounded-2xl border border-[#d5d5d5] px-4 text-sm outline-none focus:border-[#30beef] focus:ring-4 focus:ring-[#30beef]/10"
                                        />
                                    </div>

                                    <div>
                                        <label
                                            class="text-sm font-black text-neutral-800"
                                            >Colonia</label
                                        >
                                        <input
                                            v-model="form.colonia"
                                            type="text"
                                            class="mt-2 h-12 w-full rounded-2xl border border-[#d5d5d5] px-4 text-sm outline-none focus:border-[#30beef] focus:ring-4 focus:ring-[#30beef]/10"
                                        />
                                        <p
                                            v-if="errors.colonia"
                                            class="mt-2 text-xs font-bold text-red-500"
                                        >
                                            {{ errors.colonia }}
                                        </p>
                                    </div>

                                    <div>
                                        <label
                                            class="text-sm font-black text-neutral-800"
                                            >Municipio</label
                                        >
                                        <input
                                            v-model="form.municipio"
                                            type="text"
                                            class="mt-2 h-12 w-full rounded-2xl border border-[#d5d5d5] px-4 text-sm outline-none focus:border-[#30beef] focus:ring-4 focus:ring-[#30beef]/10"
                                        />
                                        <p
                                            v-if="errors.municipio"
                                            class="mt-2 text-xs font-bold text-red-500"
                                        >
                                            {{ errors.municipio }}
                                        </p>
                                    </div>

                                    <div>
                                        <label
                                            class="text-sm font-black text-neutral-800"
                                            >Estado</label
                                        >
                                        <input
                                            v-model="form.estado"
                                            type="text"
                                            class="mt-2 h-12 w-full rounded-2xl border border-[#d5d5d5] px-4 text-sm outline-none focus:border-[#30beef] focus:ring-4 focus:ring-[#30beef]/10"
                                        />
                                        <p
                                            v-if="errors.estado"
                                            class="mt-2 text-xs font-bold text-red-500"
                                        >
                                            {{ errors.estado }}
                                        </p>
                                    </div>

                                    <div>
                                        <label
                                            class="text-sm font-black text-neutral-800"
                                            >País</label
                                        >
                                        <input
                                            v-model="form.pais"
                                            type="text"
                                            class="mt-2 h-12 w-full rounded-2xl border border-[#d5d5d5] px-4 text-sm outline-none focus:border-[#30beef] focus:ring-4 focus:ring-[#30beef]/10"
                                        />
                                    </div>

                                    <div class="md:col-span-2">
                                        <label
                                            class="text-sm font-black text-neutral-800"
                                            >Referencias</label
                                        >
                                        <textarea
                                            v-model="form.referencias"
                                            rows="3"
                                            placeholder="Color de fachada, entre calles, indicaciones, etc."
                                            class="mt-2 w-full rounded-2xl border border-[#d5d5d5] px-4 py-3 text-sm outline-none focus:border-[#30beef] focus:ring-4 focus:ring-[#30beef]/10"
                                        />
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <aside class="space-y-6">
                        <section
                            class="rounded-[30px] border border-[#dfdfdf] bg-white p-6 shadow-[0_18px_60px_rgba(9,17,28,0.06)]"
                        >
                            <h2 class="text-xl font-black text-neutral-900">
                                Resumen de compra
                            </h2>

                            <div class="mt-5 divide-y divide-[#ececec]">
                                <article
                                    v-for="item in items"
                                    :key="item.producto_id"
                                    class="flex gap-4 py-4 first:pt-0"
                                >
                                    <img
                                        :src="
                                            item.imagen ||
                                            '/images/placeholder-producto.png'
                                        "
                                        :alt="item.nombre"
                                        class="h-20 w-20 rounded-2xl border border-[#e5e5e5] object-cover"
                                    />

                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="line-clamp-2 text-sm font-black text-neutral-900"
                                        >
                                            {{ item.nombre }}
                                        </p>

                                        <p
                                            class="mt-1 text-xs font-bold text-neutral-400"
                                        >
                                            SKU: {{ item.sku }}
                                        </p>

                                        <div
                                            class="mt-2 flex items-center justify-between gap-3"
                                        >
                                            <p class="text-xs text-neutral-500">
                                                Cantidad:
                                                <span
                                                    class="font-black text-neutral-800"
                                                    >{{ item.cantidad }}</span
                                                >
                                            </p>

                                            <p
                                                class="text-sm font-black text-neutral-900"
                                            >
                                                {{
                                                    formatearMoneda(
                                                        item.subtotal,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </section>

                        <section
                            class="rounded-[30px] border border-[#dfdfdf] bg-white p-6 shadow-[0_18px_60px_rgba(9,17,28,0.06)]"
                        >
                            <h2 class="text-xl font-black text-neutral-900">
                                Total
                            </h2>

                            <div class="mt-6 space-y-4 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-neutral-500"
                                        >Productos</span
                                    >
                                    <span class="font-bold text-neutral-900">{{
                                        resumen.total_productos
                                    }}</span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-neutral-500"
                                        >Subtotal</span
                                    >
                                    <span class="font-bold text-neutral-900">{{
                                        formatearMoneda(resumen.subtotal)
                                    }}</span>
                                </div>

                                <div
                                    v-if="resumen.descuento > 0"
                                    class="flex items-center justify-between"
                                >
                                    <span class="text-neutral-500"
                                        >Descuento</span
                                    >
                                    <span class="font-bold text-emerald-600"
                                        >-{{
                                            formatearMoneda(resumen.descuento)
                                        }}</span
                                    >
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-neutral-500">Envío</span>
                                    <span class="font-bold text-neutral-900">{{
                                        formatearMoneda(resumen.envio)
                                    }}</span>
                                </div>

                                <div class="border-t border-[#ececec] pt-5">
                                    <div
                                        class="flex items-end justify-between gap-3"
                                    >
                                        <span
                                            class="text-lg font-black text-neutral-900"
                                            >Total</span
                                        >
                                        <span
                                            class="text-3xl font-black tracking-tight text-neutral-900"
                                        >
                                            {{ formatearMoneda(resumen.total) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <button
                                type="submit"
                                class="mt-6 w-full rounded-full bg-gradient-to-r from-[#7dd03c] to-[#30beef] px-4 py-3.5 font-black text-white transition-all duration-300 hover:-translate-y-0.5 hover:opacity-90 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="procesando"
                            >
                                {{
                                    procesando
                                        ? 'Creando pedido...'
                                        : 'Confirmar pedido'
                                }}
                            </button>

                            <p
                                class="mt-3 text-center text-xs leading-5 text-neutral-400"
                            >
                                En el siguiente paso se integrará el cobro con
                                Openpay.
                            </p>
                        </section>
                    </aside>
                </form>
            </div>
        </section>
    </PublicLayout>
</template>
