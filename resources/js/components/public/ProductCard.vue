<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';

interface Producto {
    id: number;
    nombre: string;
    slug: string;
    sku: string;
    descripcion?: string | null;
    precio: number | string;
    precio_comparacion?: number | string | null;
    stock: number;
    imagen_principal?: string | null;
}

const props = defineProps<{
    producto: Producto;
}>();

const formatearMoneda = (valor: number | string) => {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
    }).format(Number(valor));
};

const agregarAlCarrito = () => {
    router.post(
        '/carrito/agregar',
        {
            producto_id: props.producto.id,
            cantidad: 1,
        },
        {
            preserveScroll: true,
        },
    );
};
</script>

<template>
    <div
        class="group overflow-hidden rounded-2xl border border-[var(--brand-gray)] bg-white shadow-sm transition duration-300 hover:-translate-y-1.5 hover:shadow-xl"
    >
        <div class="relative aspect-square overflow-hidden bg-[#f7f7f7]">
            <img
                :src="
                    producto.imagen_principal ||
                    '/images/placeholder-producto.png'
                "
                :alt="producto.nombre"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-110"
            />

            <div
                class="absolute top-3 left-3 rounded-full bg-white/95 px-3 py-1 text-[10px] font-black tracking-wide text-[var(--brand-blue)] uppercase shadow"
            >
                {{ producto.stock > 0 ? 'Disponible' : 'Agotado' }}
            </div>
        </div>

        <div class="space-y-3 p-4">
            <div class="min-h-[52px]">
                <h3
                    class="line-clamp-2 text-sm font-extrabold text-neutral-900 uppercase transition group-hover:text-[var(--brand-blue)]"
                >
                    {{ producto.nombre }}
                </h3>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-lg font-black text-neutral-900">
                    {{ formatearMoneda(producto.precio) }}
                </span>

                <span
                    v-if="producto.precio_comparacion"
                    class="text-sm text-neutral-400 line-through"
                >
                    {{ formatearMoneda(producto.precio_comparacion) }}
                </span>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <Link
                    :href="`/productos/${producto.slug}`"
                    class="inline-flex items-center justify-center rounded-full border border-[var(--brand-blue)] px-4 py-2 text-sm font-bold text-[var(--brand-blue)] transition hover:bg-[var(--brand-blue)] hover:text-white"
                >
                    Ver más
                </Link>

                <button
                    type="button"
                    class="rounded-full bg-[var(--brand-green)] px-4 py-2 text-sm font-bold text-white transition hover:scale-[1.02] hover:brightness-95 disabled:cursor-not-allowed disabled:bg-[var(--brand-gray)]"
                    :disabled="producto.stock < 1"
                    @click="agregarAlCarrito"
                >
                    Añadir
                </button>
            </div>
        </div>
    </div>
</template>
